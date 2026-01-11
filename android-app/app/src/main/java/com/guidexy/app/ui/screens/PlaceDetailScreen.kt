package com.guidexy.app.ui.screens

import android.content.Context
import android.content.Intent
import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.graphics.drawable.BitmapDrawable
import android.graphics.drawable.Drawable
import android.net.Uri
import android.os.Build
import android.text.Html
import android.text.method.LinkMovementMethod
import android.util.TypedValue
import android.widget.TextView
import androidx.annotation.RequiresApi
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Call
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Language
import androidx.compose.material.icons.filled.Link
import androidx.compose.material.icons.filled.Schedule
import androidx.compose.material.icons.filled.Share
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.filled.StarBorder
import androidx.compose.material.icons.filled.Visibility
import androidx.compose.material3.Card
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ScrollableTabRow
import androidx.compose.material3.Tab
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.key
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.snapshotFlow
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.viewinterop.AndroidView
import coil.compose.rememberAsyncImagePainter
import com.google.gson.Gson
import com.google.gson.reflect.TypeToken
import com.guidexy.app.data.ApiClient
import com.guidexy.app.data.PlaceDetailResponse
import com.guidexy.app.data.PlaceGalleryImageDto
import com.guidexy.app.data.PlaceKnowsAboutDto
import com.guidexy.app.data.PlaceSocialDto
import com.guidexy.app.data.PlaceServiceCategoryDto
import com.guidexy.app.data.PlaceServiceDto
import com.guidexy.app.data.PlaceServiceItemDto
import com.guidexy.app.data.PlaceServiceItemPriceDto
import com.guidexy.app.data.UserReviewDto
import com.guidexy.app.ui.PaginationConfig
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.distinctUntilChanged
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import java.io.File
import java.io.FileOutputStream
import java.text.Normalizer
import java.time.LocalDateTime
import java.time.ZoneId
import java.time.format.DateTimeFormatter
import java.net.URL

@RequiresApi(Build.VERSION_CODES.O)
@Composable
fun PlaceDetailScreen(placeId: Long, onBack: () -> Unit) {
    val detailState = remember { mutableStateOf<PlaceDetailResponse?>(null) }
    val errorState = remember { mutableStateOf<String?>(null) }
    val loadingState = remember { mutableStateOf(true) }
    val tabState = remember { mutableStateOf(0) }
    val tabs = listOf(
        "Genel",
        "Fotoğraflar",
        "Yorumlar",
        "Saatler",
        "Yoğunluk",
        "Olanaklar",
        "Hizmetler",
        "Sosyal"
    )
    val context = LocalContext.current

    if (placeId == 0L) {
        Column(modifier = Modifier.fillMaxSize().padding(16.dp)) {
            Text("Geçersiz işletme seçimi.", color = MaterialTheme.colorScheme.error)
        }
        return
    }

    LaunchedEffect(placeId) {
        loadingState.value = true
        errorState.value = null
        runCatching {
            withContext(Dispatchers.IO) { ApiClient.service.placeDetail(placeId) }
        }.onSuccess { response ->
            detailState.value = response
        }.onFailure {
            errorState.value = "Detaylar yüklenemedi."
        }
        loadingState.value = false
    }

    if (loadingState.value) {
        Column(modifier = Modifier.fillMaxSize().padding(16.dp)) {
            Text("Detaylar yükleniyor...", style = MaterialTheme.typography.titleMedium)
        }
        return
    }

    errorState.value?.let { message ->
        Column(modifier = Modifier.fillMaxSize().padding(16.dp)) {
            Text(message, color = MaterialTheme.colorScheme.error)
        }
        return
    }

    val detail = detailState.value ?: return
    val place = detail.place
    val listState = rememberLazyListState()
    val visibleReviews = remember { mutableStateOf<List<Any>>(emptyList()) }
    val reviewLoading = remember { mutableStateOf(false) }
    val galleryTabIndex = remember { mutableStateOf(0) }
    val reviewPhotoDialogState = remember { mutableStateOf<GalleryDialogState?>(null) }
    val selectedGalleryId = remember(detail.galleries) { mutableStateOf(detail.galleries.firstOrNull()?.id) }
    val galleryImages = remember(detail.gallery_images, selectedGalleryId.value) {
        detail.gallery_images.filter { it.gallery_id == selectedGalleryId.value }
    }
    val galleryDialogState = remember { mutableStateOf<GalleryDialogState?>(null) }
    val reviewSort = remember { mutableStateOf(ReviewSort.NEWEST) }
    val ratingSummary = remember { mutableStateOf(RatingSummary(0.0, 0, emptyMap())) }
    val scope = rememberCoroutineScope()
    val showStickyTabs = remember { mutableStateOf(false) }

    LaunchedEffect(listState) {
        snapshotFlow { listState.firstVisibleItemIndex to listState.firstVisibleItemScrollOffset }
            .distinctUntilChanged()
            .collect { (index, offset) ->
                val showThresholdReached = index > 0 || offset > 420
                val hideThresholdReached = index == 0 && offset < 120
                when {
                    showThresholdReached -> showStickyTabs.value = true
                    hideThresholdReached -> showStickyTabs.value = false
                }
            }
    }

    LaunchedEffect(tabState.value) {
        listState.scrollToItem(0)
        if (tabState.value != 1) {
            galleryTabIndex.value = 0
            selectedGalleryId.value = detail.galleries.firstOrNull()?.id
        }
    }

    Column(modifier = Modifier.fillMaxSize()) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(horizontal = 16.dp, vertical = 8.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            IconButton(onClick = onBack) {
                Icon(Icons.Default.ArrowBack, contentDescription = "Geri")
            }
            Text("Geri", style = MaterialTheme.typography.bodyMedium)
        }

        if (showStickyTabs.value) {
            ScrollableTabRow(selectedTabIndex = tabState.value) {
                tabs.forEachIndexed { index, title ->
                    Tab(
                        selected = tabState.value == index,
                        onClick = { tabState.value = index },
                        text = { Text(title, maxLines = 1, overflow = TextOverflow.Ellipsis) }
                    )
                }
            }
        }

        LazyColumn(state = listState, modifier = Modifier.fillMaxSize()) {
            item {
                Card(modifier = Modifier.fillMaxWidth()) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                            Image(
                                painter = rememberAsyncImagePainter(place.business_image),
                                contentDescription = place.name,
                                modifier = Modifier.width(96.dp).height(96.dp),
                                contentScale = ContentScale.Crop
                            )
                            Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
                                Text(place.name, style = MaterialTheme.typography.titleLarge)
                                val ratingText = place.rating ?: place.combined_rating
                                if (ratingText != null) {
                                    Row(verticalAlignment = Alignment.CenterVertically) {
                                        StarRating(rating = ratingText)
                                        Text("(${place.user_ratings_total ?: place.total_reviews ?: 0})", style = MaterialTheme.typography.labelSmall)
                                    }
                                }
                                Row(horizontalArrangement = Arrangement.spacedBy(6.dp), verticalAlignment = Alignment.CenterVertically) {
                                    Icon(Icons.Default.Visibility, contentDescription = null, modifier = Modifier.size(16.dp))
                                    Text("${place.views ?: 0} görüntülenme", style = MaterialTheme.typography.bodySmall)
                                }
                                Text(place.formatted_address ?: "", style = MaterialTheme.typography.bodySmall)
                                place.business_type?.let { Text(it, style = MaterialTheme.typography.bodySmall) }
                                place.current_status?.let { status ->
                                    val isOpen = status.status == "open"
                                    StatusBadge(status = PlaceStatus(status.text ?: "-", isOpen))
                                }
                            }
                        }
                    }
                }
            }

            item {
                if (!showStickyTabs.value) {
                    ScrollableTabRow(selectedTabIndex = tabState.value) {
                        tabs.forEachIndexed { index, title ->
                            Tab(
                                selected = tabState.value == index,
                                onClick = { tabState.value = index },
                                text = { Text(title, maxLines = 1, overflow = TextOverflow.Ellipsis) }
                            )
                        }
                    }
                }
            }

            item {
                when (tabState.value) {
                    0 -> GeneralTabContent(place, detail, scope, context)
                    1 -> PhotosTabContent(
                        detail = detail,
                        galleryTabIndex = galleryTabIndex,
                        reviewPhotoDialogState = reviewPhotoDialogState,
                        selectedGalleryId = selectedGalleryId,
                        galleryImages = galleryImages,
                        galleryDialogState = galleryDialogState,
                        visibleReviews = visibleReviews,
                        reviewLoading = reviewLoading
                    )
                    2 -> ReviewsTabContent(detail, ratingSummary, reviewSort, visibleReviews, reviewLoading)
                    3 -> HoursTabContent(detail)
                    4 -> BusyTabContent(detail)
                    5 -> FacilitiesTabContent(detail)
                    6 -> ServicesTabContent(detail)
                    7 -> SocialTabContent(detail)
                }
            }
        }
    }
}

@Composable
private fun GeneralTabContent(
    place: PlaceDetailResponse.Place,
    detail: PlaceDetailResponse,
    scope: kotlinx.coroutines.CoroutineScope,
    context: Context
) {
    val lat = place.latitude?.toDoubleOrNull()
    val lng = place.longitude?.toDoubleOrNull()

    Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
            if (lat != null && lng != null) {
                TextButton(onClick = {
                    val encodedName = Uri.encode(place.name)
                    val uri = Uri.parse("geo:$lat,$lng?q=$lat,$lng($encodedName)")
                    val intent = Intent(Intent.ACTION_VIEW, uri)
                    intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                    context.startActivity(intent)
                }) {
                    Text("Yol Tarifi")
                }
            }

            if (!place.formatted_phone_number.isNullOrBlank()) {
                TextButton(onClick = {
                    val intent = Intent(Intent.ACTION_DIAL, Uri.parse("tel:${place.formatted_phone_number}"))
                    intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                    context.startActivity(intent)
                }) {
                    Text("Ara")
                }
            }

            if (!place.website.isNullOrBlank()) {
                TextButton(onClick = {
                    val intent = Intent(Intent.ACTION_VIEW, Uri.parse(place.website))
                    intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                    context.startActivity(intent)
                }) {
                    Text("Web")
                }
            }

            TextButton(onClick = {
                val imageUrl = place.business_image
                val ctx = context
                scope.launch(Dispatchers.IO) {
                    try {
                        val url = URL(imageUrl)
                        val bitmap = BitmapFactory.decodeStream(url.openStream())
                        val file = File(ctx.cacheDir, "share_${place.id}.png")
                        FileOutputStream(file).use { out ->
                            bitmap.compress(Bitmap.CompressFormat.PNG, 100, out)
                        }
                        val uri = androidx.core.content.FileProvider.getUriForFile(
                            ctx,
                            "${ctx.packageName}.provider",
                            file
                        )
                        val shareIntent = Intent(Intent.ACTION_SEND).apply {
                            type = "image/png"
                            putExtra(Intent.EXTRA_STREAM, uri)
                            putExtra(Intent.EXTRA_TEXT, "${place.name}\n${place.formatted_address}")
                            addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
                        }
                        shareIntent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                        ctx.startActivity(Intent.createChooser(shareIntent, "Paylaş"))
                    } catch (_: Exception) {
                    }
                }
            }) {
                Icon(Icons.Default.Share, contentDescription = null)
                Text("Paylaş")
            }
        }

        place.description?.takeIf { it.isNotBlank() }?.let { description ->
            Card(modifier = Modifier.fillMaxWidth()) {
                Column(modifier = Modifier.padding(16.dp)) {
                    Text("Hakkında", style = MaterialTheme.typography.titleMedium)
                    Spacer(modifier = Modifier.height(8.dp))
                    HtmlText(description)
                }
            }
        }
    }
}

@Composable
private fun PhotosTabContent(
    detail: PlaceDetailResponse,
    galleryTabIndex: androidx.compose.runtime.MutableState<Int>,
    reviewPhotoDialogState: androidx.compose.runtime.MutableState<GalleryDialogState?>,
    selectedGalleryId: androidx.compose.runtime.MutableState<Long?>,
    galleryImages: List<PlaceGalleryImageDto>,
    galleryDialogState: androidx.compose.runtime.MutableState<GalleryDialogState?>,
    visibleReviews: androidx.compose.runtime.MutableState<List<Any>>,
    reviewLoading: androidx.compose.runtime.MutableState<Boolean>
) {
    Column(modifier = Modifier.fillMaxWidth().padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        ScrollableTabRow(selectedTabIndex = galleryTabIndex.value) {
            listOf("Ziyaretçi Fotoğrafları", "Galeri").forEachIndexed { index, title ->
                Tab(
                    selected = galleryTabIndex.value == index,
                    onClick = { galleryTabIndex.value = index },
                    text = { Text(title) }
                )
            }
        }

        if (galleryTabIndex.value == 0) {
            Text("Ziyaretçi fotoğrafları hazırlanıyor.")
        } else {
            LazyRow(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                items(detail.galleries.size) { index ->
                    val gallery = detail.galleries[index]
                    val isSelected = gallery.id == selectedGalleryId.value
                    TextButton(onClick = { selectedGalleryId.value = gallery.id }) {
                        Text(if (isSelected) "✓ ${gallery.name}" else gallery.name)
                    }
                }
            }

            if (galleryImages.isEmpty()) {
                Text("Galeride görsel bulunamadı.")
            } else {
                LazyRow(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                    items(galleryImages.size) { index ->
                        val image = galleryImages[index]
                        Image(
                            painter = rememberAsyncImagePainter(image.url),
                            contentDescription = image.title,
                            modifier = Modifier.size(120.dp).clickable {
                                galleryDialogState.value = GalleryDialogState(galleryImages, image)
                            },
                            contentScale = ContentScale.Crop
                        )
                    }
                }
            }
        }

        if (reviewLoading.value) {
            CircularProgressIndicator(modifier = Modifier.size(32.dp))
        }
    }

    reviewPhotoDialogState.value?.let {
        GalleryDialog(state = it) { reviewPhotoDialogState.value = null }
    }

    galleryDialogState.value?.let {
        GalleryDialog(state = it) { galleryDialogState.value = null }
    }
}

@Composable
private fun ReviewsTabContent(
    detail: PlaceDetailResponse,
    ratingSummary: androidx.compose.runtime.MutableState<RatingSummary>,
    reviewSort: androidx.compose.runtime.MutableState<ReviewSort>,
    visibleReviews: androidx.compose.runtime.MutableState<List<Any>>,
    reviewLoading: androidx.compose.runtime.MutableState<Boolean>
) {
    Column(modifier = Modifier.fillMaxWidth().padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        Text("Yorumlar", style = MaterialTheme.typography.titleMedium)
        ReviewSortRow(selected = reviewSort.value) { reviewSort.value = it }
        Text("Yorumlar hazırlanıyor.")

        if (reviewLoading.value) {
            CircularProgressIndicator(modifier = Modifier.size(32.dp))
        }
    }
}

@Composable
private fun HoursTabContent(detail: PlaceDetailResponse) {
    Column(modifier = Modifier.fillMaxWidth().padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        Text("Çalışma Saatleri", style = MaterialTheme.typography.titleMedium)
        detail.opening_hours.forEach { hour ->
            Text("${dayLabel(hour.day)}: ${hour.hours}")
        }
    }
}

@Composable
private fun BusyTabContent(detail: PlaceDetailResponse) {
    Column(modifier = Modifier.fillMaxWidth().padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        Text("Yoğunluk", style = MaterialTheme.typography.titleMedium)
        Text("Yoğunluk grafiği hazırlık aşamasında.")
    }
}

@Composable
private fun FacilitiesTabContent(detail: PlaceDetailResponse) {
    Column(modifier = Modifier.fillMaxWidth().padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        Text("Olanaklar", style = MaterialTheme.typography.titleMedium)
        detail.knows_about.forEach { item ->
            Text("• ${item.value}")
        }
    }
}

@Composable
private fun ServicesTabContent(detail: PlaceDetailResponse) {
    Column(modifier = Modifier.fillMaxWidth().padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        Text("Hizmetler", style = MaterialTheme.typography.titleMedium)
        val sections = buildServiceSections(detail.services, detail.service_categories, detail.service_items, detail.service_item_prices)
        sections.forEach { section ->
            ServiceSectionCard(section)
        }
    }
}

@Composable
private fun SocialTabContent(detail: PlaceDetailResponse) {
    Column(modifier = Modifier.fillMaxWidth().padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        Text("Sosyal", style = MaterialTheme.typography.titleMedium)
        if (detail.social.isEmpty()) {
            Text("Sosyal medya bağlantısı bulunamadı.")
        } else {
            detail.social.forEach { SocialLinkCard(it) }
        }
    }
}

@Composable
private fun HtmlText(html: String) {
    val context = LocalContext.current
    AndroidView(factory = {
        TextView(context).apply {
            setTextColor(android.graphics.Color.BLACK)
            setTextSize(TypedValue.COMPLEX_UNIT_SP, 14f)
            movementMethod = LinkMovementMethod.getInstance()
        }
    }) { view ->
        view.text = Html.fromHtml(html, Html.FROM_HTML_MODE_LEGACY)
    }
}

@Composable
private fun StarRating(rating: Float) {
    Row(horizontalArrangement = Arrangement.spacedBy(2.dp)) {
        val fullStars = rating.toInt()
        repeat(5) { index ->
            val icon = if (index < fullStars) Icons.Default.Star else Icons.Default.StarBorder
            Icon(icon, contentDescription = null, tint = MaterialTheme.colorScheme.primary)
        }
    }
}

@Composable
private fun StatusBadge(status: PlaceStatus) {
    val color = if (status.isOpen) Color(0xFF2E7D32) else Color(0xFFC62828)
    Box(
        modifier = Modifier
            .background(color.copy(alpha = 0.1f))
            .padding(horizontal = 8.dp, vertical = 4.dp)
    ) {
        Text(status.label, color = color, style = MaterialTheme.typography.labelSmall)
    }
}

@Composable
private fun ReviewSortRow(selected: ReviewSort, onSelect: (ReviewSort) -> Unit) {
    Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
        ReviewSort.values().forEach { sort ->
            TextButton(onClick = { onSelect(sort) }) {
                Text(if (sort == selected) "✓ ${sort.label}" else sort.label)
            }
        }
    }
}

@Composable
private fun SocialLinkCard(link: PlaceSocialDto) {
    val label = link.label.ifBlank { link.url }
    val icon = socialIconForLabel(label)
    val context = LocalContext.current
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .clickable {
                val uri = Uri.parse(link.url)
                val intent = Intent(Intent.ACTION_VIEW, uri)
                intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                context.startActivity(intent)
            }
    ) {
        Row(
            modifier = Modifier.padding(horizontal = 16.dp, vertical = 12.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Text(icon, style = MaterialTheme.typography.titleMedium)
            Column(modifier = Modifier.weight(1f)) {
                Text(label, style = MaterialTheme.typography.titleSmall)
                Text(link.url, style = MaterialTheme.typography.labelSmall, maxLines = 1)
            }
            Icon(Icons.Default.Link, contentDescription = null)
        }
    }
}

@Composable
private fun GalleryDialog(state: GalleryDialogState, onDismiss: () -> Unit) {
    androidx.compose.ui.window.Dialog(onDismissRequest = onDismiss) {
        Card(modifier = Modifier.fillMaxWidth()) {
            Column(modifier = Modifier.padding(16.dp)) {
                Text(state.selected.title ?: "", style = MaterialTheme.typography.titleMedium)
                Spacer(modifier = Modifier.height(12.dp))
                Image(
                    painter = rememberAsyncImagePainter(state.selected.url),
                    contentDescription = state.selected.title,
                    modifier = Modifier.fillMaxWidth().height(240.dp),
                    contentScale = ContentScale.Crop
                )
            }
        }
    }
}

private data class RatingSummary(
    val average: Double,
    val total: Int,
    val distribution: Map<Int, Int>
)

private enum class ReviewSort(val label: String) {
    NEWEST("En yeni"),
    OLDEST("En eski"),
    HIGHEST("En yüksek"),
    LOWEST("En düşük")
}

private data class GalleryDialogState(
    val images: List<PlaceGalleryImageDto>,
    val selected: PlaceGalleryImageDto
)

private data class PlaceStatus(
    val label: String,
    val isOpen: Boolean
)

@Composable
private fun ServiceSectionCard(section: ServiceSection) {
    val context = LocalContext.current
    Card(modifier = Modifier.fillMaxWidth()) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text(section.title, style = MaterialTheme.typography.titleMedium)
            Spacer(modifier = Modifier.height(8.dp))
            section.items.forEach { item ->
                Card(
                    modifier = Modifier.fillMaxWidth().padding(vertical = 6.dp),
                    border = BorderStroke(1.dp, MaterialTheme.colorScheme.outline)
                ) {
                    Row(modifier = Modifier.padding(12.dp), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                        if (!item.imageUrl.isNullOrBlank()) {
                            Image(
                                painter = rememberAsyncImagePainter(item.imageUrl),
                                contentDescription = item.title,
                                contentScale = ContentScale.Crop,
                                modifier = Modifier.width(72.dp).height(72.dp)
                            )
                        }
                        Column(modifier = Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                            Row(horizontalArrangement = Arrangement.spacedBy(6.dp), verticalAlignment = Alignment.CenterVertically) {
                                Icon(Icons.Default.CheckCircle, contentDescription = null)
                                Text(item.title, style = MaterialTheme.typography.titleSmall)
                            }
                            item.description?.let { desc ->
                                Text(desc, style = MaterialTheme.typography.bodySmall, maxLines = 3, overflow = TextOverflow.Ellipsis)
                            }
                            item.priceText?.let { price ->
                                Text(price, style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.SemiBold)
                            }
                            val buttonLabel = item.buttonLabel?.takeIf { it.isNotBlank() } ?: "Detay"
                            TextButton(
                                onClick = {
                                    val uri = Uri.parse(item.buttonUrl)
                                    val intent = Intent(Intent.ACTION_VIEW, uri)
                                    intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                                    context.startActivity(intent)
                                },
                                enabled = !item.buttonUrl.isNullOrBlank()
                            ) {
                                Text(buttonLabel)
                            }
                        }
                    }
                }
            }
        }
    }
}

private data class ServiceSection(
    val title: String,
    val items: List<ServiceItem>
)

private data class ServiceItem(
    val title: String,
    val description: String?,
    val priceText: String?,
    val buttonLabel: String?,
    val buttonUrl: String?,
    val imageUrl: String?
)

private fun dayLabel(day: Int): String {
    return when (day) {
        1 -> "Pazartesi"
        2 -> "Salı"
        3 -> "Çarşamba"
        4 -> "Perşembe"
        5 -> "Cuma"
        6 -> "Cumartesi"
        7 -> "Pazar"
        else -> "Gün $day"
    }
}

private fun socialIconForLabel(label: String): String {
    val l = label.lowercase()
    return when {
        l.contains("instagram") -> "📸"
        l.contains("facebook") -> "📘"
        l.contains("twitter") || l.contains("x") -> "𝕏"
        l.contains("youtube") -> "▶️"
        l.contains("tiktok") -> "🎵"
        l.contains("linkedin") -> "💼"
        l.contains("whatsapp") -> "💬"
        else -> "🌐"
    }
}

private fun buildServiceSections(
    services: List<PlaceServiceDto>,
    categories: List<PlaceServiceCategoryDto>,
    items: List<PlaceServiceItemDto>,
    prices: List<PlaceServiceItemPriceDto>
): List<ServiceSection> {
    val pricesByItem = prices.groupBy { it.item_id }
    return services.flatMap { service ->
        val serviceCategories = categories.filter { it.service_id == service.id }
        if (serviceCategories.isEmpty()) {
            val serviceItems = items.filter { it.category_id == service.id }
            if (serviceItems.isEmpty()) {
                emptyList()
            } else {
                listOf(
                    ServiceSection(
                        title = service.title,
                        items = serviceItems.map { item -> buildServiceItem(item, pricesByItem[item.id].orEmpty()) }
                    )
                )
            }
        } else {
            serviceCategories.map { category ->
                val categoryItems = items.filter { it.category_id == category.id }
                ServiceSection(
                    title = category.title,
                    items = categoryItems.map { item -> buildServiceItem(item, pricesByItem[item.id].orEmpty()) }
                )
            }
        }
    }.filter { it.items.isNotEmpty() }
}

private fun buildServiceItem(
    item: PlaceServiceItemDto,
    prices: List<PlaceServiceItemPriceDto>
): ServiceItem {
    val priceText = when {
        prices.isNotEmpty() -> prices.joinToString(" • ") { price ->
            "${price.title} ${price.currency_symbol}${price.price}"
        }
        !item.price.isNullOrBlank() && !item.currency_symbol.isNullOrBlank() ->
            "${item.currency_symbol}${item.price}"
        else -> null
    }
    return ServiceItem(
        title = item.title,
        description = item.description,
        priceText = priceText,
        buttonLabel = item.button_label,
        buttonUrl = item.button_url,
        imageUrl = item.image_url
    )
}
