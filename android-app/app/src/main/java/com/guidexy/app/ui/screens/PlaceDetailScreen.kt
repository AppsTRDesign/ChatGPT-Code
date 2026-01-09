package com.guidexy.app.ui.screens

import android.content.Intent
import android.net.Uri
import android.text.method.LinkMovementMethod
import android.widget.TextView
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
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Link
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.filled.StarBorder
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ScrollableTabRow
import androidx.compose.material3.Tab
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.snapshotFlow
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.compose.ui.window.Dialog
import androidx.core.text.HtmlCompat
import coil.compose.rememberAsyncImagePainter
import com.google.gson.Gson
import com.google.gson.reflect.TypeToken
import com.guidexy.app.data.ApiClient
import com.guidexy.app.data.GoogleReviewDto
import com.guidexy.app.data.PlaceDetailResponse
import com.guidexy.app.data.PlaceGalleryImageDto
import com.guidexy.app.data.PlaceHoursDto
import com.guidexy.app.data.PlaceKnowsAboutDto
import com.guidexy.app.data.PlaceSocialDto
import com.guidexy.app.data.PlaceServiceCategoryDto
import com.guidexy.app.data.PlaceServiceDto
import com.guidexy.app.data.PlaceServiceItemDto
import com.guidexy.app.data.PlaceServiceItemPriceDto
import com.guidexy.app.data.UserReviewDto
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.distinctUntilChanged
import kotlinx.coroutines.withContext
import java.time.LocalDateTime
import java.time.LocalTime
import java.time.ZoneId
import java.time.format.DateTimeFormatter
import java.util.Locale

@OptIn(ExperimentalMaterial3Api::class)
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
        "Yoğun",
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
    val googleReviews by remember(place.reviews) {
        val type = object : TypeToken<List<GoogleReviewDto>>() {}.type
        val parsed = runCatching {
            if (place.reviews.isNullOrBlank()) {
                emptyList()
            } else {
                Gson().fromJson<List<GoogleReviewDto>>(place.reviews, type)
            }
        }.getOrDefault(emptyList())
        mutableStateOf(parsed)
    }
    val reviewSort = remember { mutableStateOf(ReviewSort.NEWEST) }
    val listState = rememberLazyListState()
    val visibleCount = remember { mutableStateOf(10) }
    val combinedReviews = remember(googleReviews, detail.user_reviews) {
        buildCombinedReviews(googleReviews, detail.user_reviews)
    }
    val sortedReviews = remember(combinedReviews, reviewSort.value) {
        sortCombinedReviews(combinedReviews, reviewSort.value)
    }
    val visibleReviews = remember(sortedReviews, visibleCount.value) {
        sortedReviews.take(visibleCount.value)
    }
    val ratingSummary = remember(sortedReviews) { buildRatingSummary(sortedReviews) }
    val selectedGalleryId = remember(detail.galleries) {
        mutableStateOf(detail.galleries.firstOrNull()?.id)
    }
    val galleryImages = remember(detail.gallery_images, selectedGalleryId.value) {
        detail.gallery_images.filter { it.gallery_id == selectedGalleryId.value }
    }
    val galleryDialogState = remember { mutableStateOf<GalleryDialogState?>(null) }

    LaunchedEffect(listState, sortedReviews.size, tabState.value) {
        snapshotFlow { listState.layoutInfo.visibleItemsInfo.lastOrNull()?.index }
            .distinctUntilChanged()
            .collect { index ->
                if (tabState.value != 2) {
                    return@collect
                }
                if (index != null && index >= visibleReviews.size - 2 && visibleCount.value < sortedReviews.size) {
                    visibleCount.value = (visibleCount.value + 10).coerceAtMost(sortedReviews.size)
                }
            }
    }

    Column(modifier = Modifier.fillMaxSize()) {
        Text(
            text = "Keşfete Dön",
            color = MaterialTheme.colorScheme.primary,
            modifier = Modifier
                .padding(horizontal = 16.dp, vertical = 8.dp)
                .clickable { onBack() }
        )
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
                        Text(place.formatted_address ?: "", style = MaterialTheme.typography.bodySmall)
                        place.business_type?.let { Text(it, style = MaterialTheme.typography.bodySmall) }
                        val status = computePlaceStatus(detail.hours)
                        StatusBadge(status = status)
                    }
                }
                Spacer(modifier = Modifier.height(12.dp))
                val lat = place.latitude?.toDoubleOrNull()
                val lng = place.longitude?.toDoubleOrNull()
                if (lat != null && lng != null) {
                    Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                        Button(onClick = onBack) {
                            Text("Keşfet")
                        }
                        Button(onClick = {
                            val encodedName = Uri.encode(place.name)
                            val uri = Uri.parse("geo:$lat,$lng?q=$lat,$lng($encodedName)")
                            val intent = Intent(Intent.ACTION_VIEW, uri)
                            intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                            context.startActivity(intent)
                        }) {
                            Text("Yol Tarifi")
                        }
                    }
                }
            }
        }
        if (detail.gallery_images.isNotEmpty()) {
            LazyRow(
                modifier = Modifier.padding(top = 12.dp, start = 16.dp, end = 16.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                items(detail.gallery_images.take(5)) { image ->
                    Card(modifier = Modifier.width(160.dp).height(110.dp)) {
                        Image(
                            painter = rememberAsyncImagePainter(image.image_url),
                            contentDescription = place.name,
                            contentScale = ContentScale.Crop,
                            modifier = Modifier.fillMaxSize()
                        )
                    }
                }
            }
        }
        ScrollableTabRow(selectedTabIndex = tabState.value, edgePadding = 12.dp) {
            tabs.forEachIndexed { index, label ->
                Tab(selected = tabState.value == index, onClick = { tabState.value = index }, text = { Text(label) })
            }
        }
        LazyColumn(
            state = listState,
            modifier = Modifier.fillMaxSize().padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            when (tabState.value) {
                0 -> {
                    item {
                        HtmlText(place.description ?: "Açıklama yok")
                        Spacer(modifier = Modifier.height(8.dp))
                        InfoRow(label = "Telefon", value = place.formatted_phone_number ?: "-")
                        InfoRow(label = "Web", value = place.website ?: "-")
                        if (detail.social_links.isNotEmpty()) {
                            Text("Sosyal Bağlantılar", style = MaterialTheme.typography.titleMedium)
                            detail.social_links.forEach { link ->
                                SocialLinkItem(link = link)
                            }
                        }
                    }
                }
                1 -> {
                    item {
                        Text("Galeriler", style = MaterialTheme.typography.titleMedium)
                        LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            items(detail.galleries) { gallery ->
                                Card(
                                    modifier = Modifier
                                        .clickable { selectedGalleryId.value = gallery.id }
                                        .padding(vertical = 4.dp)
                                ) {
                                    Column(modifier = Modifier.padding(horizontal = 12.dp, vertical = 8.dp)) {
                                        Text(gallery.title)
                                    }
                                }
                            }
                        }
                        if (galleryImages.isEmpty()) {
                            Text("Bu galeri için görsel bulunamadı.")
                        }
                    }
                    if (galleryImages.isNotEmpty()) {
                        item {
                            LazyVerticalGrid(
                                columns = GridCells.Fixed(2),
                                modifier = Modifier.fillMaxWidth().height(360.dp),
                                horizontalArrangement = Arrangement.spacedBy(8.dp),
                                verticalArrangement = Arrangement.spacedBy(8.dp)
                            ) {
                                items(galleryImages) { image ->
                                    Card(modifier = Modifier.clickable {
                                        galleryDialogState.value = GalleryDialogState(galleryImages, image)
                                    }) {
                                        Image(
                                            painter = rememberAsyncImagePainter(image.thumb_url.ifBlank { image.image_url }),
                                            contentDescription = null,
                                            contentScale = ContentScale.Crop,
                                            modifier = Modifier.fillMaxWidth().height(140.dp)
                                        )
                                    }
                                }
                            }
                        }
                    }
                }
                2 -> {
                    item {
                        ReviewSummaryCard(summary = ratingSummary)
                    }
                    item {
                        ReviewSortRow(selected = reviewSort.value, onSelect = {
                            reviewSort.value = it
                            visibleCount.value = 10
                        })
                    }
                    if (sortedReviews.isEmpty()) {
                        item { Text("Henüz yorum yok.") }
                    } else {
                        items(visibleReviews) { review ->
                            ReviewCard(review = review)
                        }
                        if (visibleReviews.size < sortedReviews.size) {
                            item { Text("Daha fazla yorum yükleniyor...") }
                        } else {
                            item { Text("Tüm yorumlar yüklendi.") }
                        }
                    }
                }
                3 -> {
                    items(detail.hours) { hour ->
                        val dayLabel = dayLabel(hour.day)
                        val statusText = when {
                            hour.is_closed == 1 -> "Kapalı"
                            hour.is_24h == 1 -> "24 saat açık"
                            hour.open_time.isNullOrBlank() && hour.close_time.isNullOrBlank() -> "-"
                            else -> "${hour.open_time ?: ""} - ${hour.close_time ?: ""}"
                        }
                        InfoRow(label = dayLabel, value = statusText)
                    }
                }
                4 -> {
                    val busyHours = parseBusyHours(place.busy_hours)
                    if (busyHours.isEmpty()) {
                        item { Text("Yoğun saat verisi bulunamadı.") }
                    } else {
                        item { BusyHoursChart(busyHours = busyHours) }
                    }
                }
                5 -> {
                    val grouped = groupServices(detail.knows_about)
                    if (grouped.isEmpty()) {
                        item { Text("Olanak bulunamadı.") }
                    } else {
                        grouped.forEach { group ->
                            item {
                                ServiceGroupCard(group = group)
                            }
                        }
                    }
                }
                6 -> {
                    val serviceSections = buildServiceSections(
                        services = detail.services,
                        categories = detail.service_categories,
                        items = detail.service_items,
                        prices = detail.service_item_prices
                    )
                    if (serviceSections.isEmpty()) {
                        item { Text("Hizmet bulunamadı.") }
                    } else {
                        serviceSections.forEach { section ->
                            item { ServiceSectionCard(section) }
                        }
                    }
                }
                7 -> {
                    if (detail.social_links.isEmpty()) {
                        item { Text("Sosyal bağlantı yok.") }
                    } else {
                        items(detail.social_links) { link ->
                            SocialLinkItem(link = link)
                        }
                    }
                }
            }
        }
    }

    galleryDialogState.value?.let { state ->
        GalleryDialog(state = state, onDismiss = { galleryDialogState.value = null })
    }
}

@Composable
private fun ReviewCard(review: CombinedReview) {
    val expanded = remember { mutableStateOf(false) }
    val dialogState = remember { mutableStateOf<GalleryDialogState?>(null) }
    Card {
        Column(modifier = Modifier.padding(12.dp)) {
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                ReviewAvatar(review)
                Column {
                    Text(review.authorName ?: "Yorumcu", style = MaterialTheme.typography.titleMedium)
                    review.rating?.let { StarRating(rating = it.toDouble()) }
                    review.createdAt?.let { Text(it, style = MaterialTheme.typography.labelSmall) }
                }
            }
            review.text?.let { text ->
                Text(
                    text = text,
                    maxLines = if (expanded.value) Int.MAX_VALUE else 3,
                    overflow = TextOverflow.Ellipsis
                )
                if (text.length > 120) {
                    TextButton(onClick = { expanded.value = !expanded.value }) {
                        Text(if (expanded.value) "Kısalt" else "Tümünü Gör")
                    }
                }
            }
            if (review.textExtra.isNotEmpty()) {
                Spacer(modifier = Modifier.height(8.dp))
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    colors = CardDefaults.cardColors(
                        containerColor = MaterialTheme.colorScheme.primary.copy(alpha = 0.05f)
                    )
                ) {
                    Column(modifier = Modifier.padding(8.dp)) {
                        review.textExtra.forEach { (key, value) ->
                            Text("$key: $value", style = MaterialTheme.typography.labelSmall)
                        }
                    }
                }
            }
            if (review.photoUrls.isNotEmpty()) {
                Spacer(modifier = Modifier.height(8.dp))
                LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    items(review.photoUrls) { url ->
                        val isLead = url == review.photoUrls.first()
                        Card(modifier = Modifier
                            .width(if (isLead) 180.dp else 120.dp)
                            .height(if (isLead) 120.dp else 80.dp)
                            .clickable {
                                dialogState.value = GalleryDialogState(
                                    images = review.photoUrls.mapIndexed { index, item ->
                                        PlaceGalleryImageDto(
                                            id = index.toLong(),
                                            gallery_id = 0L,
                                            image_url = item,
                                            thumb_url = item
                                        )
                                    },
                                    selected = PlaceGalleryImageDto(
                                        id = 0L,
                                        gallery_id = 0L,
                                        image_url = url,
                                        thumb_url = url
                                    )
                                )
                            }) {
                            Image(
                                painter = rememberAsyncImagePainter(url),
                                contentDescription = null,
                                contentScale = ContentScale.Crop,
                                modifier = Modifier.fillMaxSize()
                            )
                        }
                    }
                }
            }
        }
    }

    dialogState.value?.let { state ->
        GalleryDialog(state = state, onDismiss = { dialogState.value = null })
    }
}

@Composable
private fun ReviewSummaryCard(summary: RatingSummary) {
    Card(modifier = Modifier.fillMaxWidth()) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text("Toplam Puan", style = MaterialTheme.typography.titleMedium)
            Row(verticalAlignment = Alignment.CenterVertically) {
                Text(
                    summary.average.toString(),
                    style = MaterialTheme.typography.displaySmall,
                    fontWeight = FontWeight.Bold
                )
                Spacer(modifier = Modifier.width(12.dp))
                Column {
                    StarRating(rating = summary.average.toDouble())
                    Text("${summary.total} yorum", style = MaterialTheme.typography.labelSmall)
                }
            }
            Spacer(modifier = Modifier.height(12.dp))
            summary.distribution.entries.sortedByDescending { it.key }.forEach { (rating, count) ->
                RatingProgressRow(rating = rating, count = count, total = summary.total)
            }
        }
    }
}

@Composable
private fun RatingProgressRow(rating: Int, count: Int, total: Int) {
    Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.padding(vertical = 4.dp)) {
        Text("$rating", modifier = Modifier.width(18.dp))
        StarRating(rating = rating.toDouble())
        Spacer(modifier = Modifier.width(8.dp))
        Box(
            modifier = Modifier
                .weight(1f)
                .height(8.dp)
                .background(MaterialTheme.colorScheme.surfaceVariant)
        ) {
            val ratio = if (total == 0) 0f else count.toFloat() / total
            Box(
                modifier = Modifier
                    .fillMaxHeight()
                    .fillMaxWidth(ratio)
                    .background(MaterialTheme.colorScheme.primary)
            )
        }
        Spacer(modifier = Modifier.width(8.dp))
        Text("$count", style = MaterialTheme.typography.labelSmall)
    }
}

@Composable
private fun StarRating(rating: Double) {
    val filled = rating.toInt().coerceIn(0, 5)
    Row(verticalAlignment = Alignment.CenterVertically) {
        repeat(5) { index ->
            val icon = if (index < filled) Icons.Default.Star else Icons.Default.StarBorder
            androidx.compose.material3.Icon(
                icon,
                contentDescription = null,
                tint = Color(0xFFFFC107),
                modifier = Modifier.size(16.dp)
            )
        }
    }
}

@Composable
private fun StatusBadge(status: PlaceStatus) {
    val color = if (status.isOpen) Color(0xFF2E7D32) else Color(0xFFC62828)
    Box(
        modifier = Modifier
            .background(color.copy(alpha = 0.15f))
            .padding(horizontal = 8.dp, vertical = 4.dp)
    ) {
        Text(status.label, color = color, style = MaterialTheme.typography.labelSmall)
    }
}

@Composable
private fun ReviewAvatar(review: CombinedReview) {
    val imageUrl = review.profilePhotoUrl
    if (!imageUrl.isNullOrBlank()) {
        Card(modifier = Modifier.width(48.dp).height(48.dp)) {
            Image(
                painter = rememberAsyncImagePainter(imageUrl),
                contentDescription = review.authorName,
                contentScale = ContentScale.Crop,
                modifier = Modifier.fillMaxSize()
            )
        }
    } else {
        Card(modifier = Modifier.width(48.dp).height(48.dp)) {
            Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                Text(text = review.authorName?.take(1)?.uppercase() ?: "?")
            }
        }
    }
}

@Composable
private fun HtmlText(html: String) {
    AndroidView(
        factory = {
            TextView(it).apply {
                movementMethod = LinkMovementMethod.getInstance()
                textSize = 14f
                setLineSpacing(6f, 1.2f)
            }
        },
        update = { view ->
            view.text = HtmlCompat.fromHtml(html, HtmlCompat.FROM_HTML_MODE_COMPACT)
        }
    )
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
private fun InfoRow(label: String, value: String) {
    Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
        Text(label, style = MaterialTheme.typography.bodyMedium)
        Text(value, style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.Medium)
    }
}

@Composable
private fun SocialLinkItem(link: PlaceSocialDto) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clickable {
                val uri = Uri.parse(link.url)
                val intent = Intent(Intent.ACTION_VIEW, uri)
                intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                LocalContext.current.startActivity(intent)
            }
            .padding(vertical = 4.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        androidx.compose.material3.Icon(Icons.Default.Link, contentDescription = null)
        Spacer(modifier = Modifier.width(8.dp))
        Text(link.label.ifBlank { link.url }, style = MaterialTheme.typography.bodySmall)
    }
}

@Composable
private fun BusyHoursChart(busyHours: Map<String, List<BusyHour>>) {
    val today = dayName(LocalDateTime.now().dayOfWeek.value)
    val entries = busyHours[today] ?: busyHours.values.firstOrNull().orEmpty()
    Card(modifier = Modifier.fillMaxWidth()) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text("Yoğun Saatler ($today)", style = MaterialTheme.typography.titleMedium)
            if (entries.isEmpty()) {
                Text("Veri bulunamadı.")
            } else {
                val max = entries.maxOf { it.value }.coerceAtLeast(1)
                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                    entries.take(12).forEach { entry ->
                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                            Box(
                                modifier = Modifier
                                    .width(12.dp)
                                    .height((80 * entry.value / max).coerceAtLeast(4).dp)
                                    .background(MaterialTheme.colorScheme.primary)
                            )
                            Text(entry.hour, style = MaterialTheme.typography.labelSmall)
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun ServiceGroupCard(group: ServiceGroup) {
    Card(modifier = Modifier.fillMaxWidth()) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text("${group.icon} ${group.title}", style = MaterialTheme.typography.titleMedium)
            Spacer(modifier = Modifier.height(8.dp))
            group.items.forEach { item ->
                Text("• $item", style = MaterialTheme.typography.bodySmall)
            }
        }
    }
}

@Composable
private fun GalleryDialog(state: GalleryDialogState, onDismiss: () -> Unit) {
    Dialog(onDismissRequest = onDismiss) {
        Card(modifier = Modifier.fillMaxWidth()) {
            Column(modifier = Modifier.padding(12.dp)) {
                Text("Galeri", style = MaterialTheme.typography.titleMedium)
                Spacer(modifier = Modifier.height(12.dp))
                LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    items(state.images) { image ->
                        Image(
                            painter = rememberAsyncImagePainter(image.image_url),
                            contentDescription = null,
                            contentScale = ContentScale.Crop,
                            modifier = Modifier
                                .width(280.dp)
                                .height(200.dp)
                        )
                    }
                }
                Spacer(modifier = Modifier.height(12.dp))
                TextButton(onClick = onDismiss) { Text("Kapat") }
            }
        }
    }
}

private data class CombinedReview(
    val id: String,
    val authorName: String?,
    val rating: Int?,
    val text: String?,
    val createdAt: String?,
    val sortTimestamp: Long?,
    val profilePhotoUrl: String?,
    val textExtra: Map<String, String>,
    val photoUrls: List<String>
)

private enum class ReviewSort(val label: String) {
    NEWEST("En yeni"),
    OLDEST("En eski"),
    HIGHEST("En yüksek"),
    LOWEST("En düşük")
}

private data class RatingSummary(
    val average: Double,
    val total: Int,
    val distribution: Map<Int, Int>
)

private data class BusyHour(
    val hour: String,
    val value: Int
)

private data class GalleryDialogState(
    val images: List<PlaceGalleryImageDto>,
    val selected: PlaceGalleryImageDto
)

private data class ServiceGroup(
    val key: String,
    val icon: String,
    val title: String,
    val items: List<String>
)

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

private data class PlaceStatus(
    val label: String,
    val isOpen: Boolean
)

@Composable
private fun ServiceSectionCard(section: ServiceSection) {
    Card(modifier = Modifier.fillMaxWidth()) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text(section.title, style = MaterialTheme.typography.titleMedium)
            Spacer(modifier = Modifier.height(8.dp))
            section.items.forEach { item ->
                Card(modifier = Modifier.fillMaxWidth().padding(vertical = 6.dp)) {
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
                            Text(item.title, style = MaterialTheme.typography.titleSmall)
                            item.description?.let { desc ->
                                Text(desc, style = MaterialTheme.typography.bodySmall, maxLines = 3, overflow = TextOverflow.Ellipsis)
                            }
                            item.priceText?.let { price ->
                                Text(price, style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.SemiBold)
                            }
                            if (!item.buttonLabel.isNullOrBlank() && !item.buttonUrl.isNullOrBlank()) {
                                TextButton(onClick = {
                                    val uri = Uri.parse(item.buttonUrl)
                                    val intent = Intent(Intent.ACTION_VIEW, uri)
                                    intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                                    LocalContext.current.startActivity(intent)
                                }) {
                                    Text(item.buttonLabel)
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

private fun buildCombinedReviews(
    googleReviews: List<GoogleReviewDto>,
    userReviews: List<UserReviewDto>
): List<CombinedReview> {
    val google = googleReviews.mapIndexed { index, review ->
        CombinedReview(
            id = "google-$index",
            authorName = review.author_name,
            rating = review.rating,
            text = review.text,
            createdAt = review.relative_time_raw ?: review.relative_time,
            sortTimestamp = parseDate(review.relative_time_raw ?: review.relative_time),
            profilePhotoUrl = review.profile_photo_url,
            textExtra = review.text_extra ?: emptyMap(),
            photoUrls = review.review_photo_urls ?: emptyList()
        )
    }
    val user = userReviews.mapIndexed { index, review ->
        CombinedReview(
            id = "user-$index",
            authorName = review.author_name,
            rating = review.rating,
            text = review.review_text,
            createdAt = review.created_at,
            sortTimestamp = parseDate(review.created_at),
            profilePhotoUrl = null,
            textExtra = review.text_extra ?: emptyMap(),
            photoUrls = review.review_photo_urls ?: emptyList()
        )
    }
    return google + user
}

private fun sortCombinedReviews(
    reviews: List<CombinedReview>,
    sort: ReviewSort
): List<CombinedReview> {
    return when (sort) {
        ReviewSort.NEWEST -> reviews.sortedByDescending { it.sortTimestamp ?: 0L }
        ReviewSort.OLDEST -> reviews.sortedBy { it.sortTimestamp ?: Long.MAX_VALUE }
        ReviewSort.HIGHEST -> reviews.sortedByDescending { it.rating ?: 0 }
        ReviewSort.LOWEST -> reviews.sortedBy { it.rating ?: Int.MAX_VALUE }
    }
}

private fun buildRatingSummary(reviews: List<CombinedReview>): RatingSummary {
    if (reviews.isEmpty()) {
        return RatingSummary(0.0, 0, emptyMap())
    }
    val distribution = (1..5).associateWith { rating ->
        reviews.count { it.rating == rating }
    }
    val avg = reviews.mapNotNull { it.rating }.average().takeIf { !it.isNaN() } ?: 0.0
    return RatingSummary(
        average = String.format(Locale.getDefault(), "%.1f", avg).toDouble(),
        total = reviews.size,
        distribution = distribution
    )
}

private fun parseDate(value: String?): Long? {
    if (value.isNullOrBlank()) {
        return null
    }
    return runCatching {
        val formatter = DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss")
        val dateTime = LocalDateTime.parse(value, formatter)
        dateTime.atZone(ZoneId.systemDefault()).toInstant().toEpochMilli()
    }.getOrNull()
}

private fun computePlaceStatus(hours: List<PlaceHoursDto>): PlaceStatus {
    if (hours.isEmpty()) {
        return PlaceStatus("Çalışma saatleri bulunamadı", false)
    }
    val now = LocalDateTime.now(ZoneId.of("Europe/Istanbul"))
    val today = now.dayOfWeek.value
    val todayHours = hours.firstOrNull { it.day == today }
    if (todayHours == null || todayHours.is_closed == 1) {
        return PlaceStatus("Bugün kapalı", false)
    }
    if (todayHours.is_24h == 1) {
        return PlaceStatus("Şu an açık • 24 saat hizmet veriyor", true)
    }
    val openTime = parseLocalTime(todayHours.open_time)
    val closeTime = parseLocalTime(todayHours.close_time)
    if (openTime == null || closeTime == null) {
        return PlaceStatus("Çalışma saatleri bulunamadı", false)
    }
    val currentTime = now.toLocalTime()
    val crossesDay = closeTime.isBefore(openTime)

    val isOpen = if (crossesDay) {
        currentTime.isAfter(openTime) || currentTime.isBefore(closeTime)
    } else {
        (currentTime == openTime || currentTime.isAfter(openTime)) &&
            (currentTime == closeTime || currentTime.isBefore(closeTime))
    }

    return if (isOpen) {
        PlaceStatus("Şu an açık • ${todayHours.close_time}’da kapanıyor", true)
    } else {
        if (crossesDay) {
            PlaceStatus("${todayHours.open_time}’de açılacak", false)
        } else if (currentTime.isBefore(openTime)) {
            PlaceStatus("${todayHours.open_time}’de açılacak", false)
        } else {
            PlaceStatus("Kapalı", false)
        }
    }
}

private fun parseLocalTime(value: String?): LocalTime? {
    if (value.isNullOrBlank()) return null
    return runCatching {
        LocalTime.parse(value, DateTimeFormatter.ofPattern("HH:mm"))
    }.getOrNull()
}

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

private fun dayName(day: Int): String {
    return when (day) {
        1 -> "Pazartesi"
        2 -> "Salı"
        3 -> "Çarşamba"
        4 -> "Perşembe"
        5 -> "Cuma"
        6 -> "Cumartesi"
        7 -> "Pazar"
        else -> "Pazartesi"
    }
}

private fun parseBusyHours(raw: String?): Map<String, List<BusyHour>> {
    if (raw.isNullOrBlank()) return emptyMap()
    return runCatching {
        val type = object : TypeToken<Map<String, List<Map<String, Any>>>>() {}.type
        val parsed: Map<String, List<Map<String, Any>>> = Gson().fromJson(raw, type)
        parsed.mapValues { entry ->
            entry.value.mapNotNull { row ->
                val hourValue = (row["saat"] ?: row["hour"] ?: row["time"])?.toString() ?: return@mapNotNull null
                val rawBusy = row["busy"] ?: row["yogunluk"] ?: row["yoğunluk"] ?: row["load"] ?: row["value"]
                val busy = rawBusy?.toString()?.replace("%", "")?.toIntOrNull() ?: 0
                BusyHour(hour = hourValue.take(2), value = busy)
            }
        }
    }.getOrDefault(emptyMap())
}

private fun groupServices(knowsAbout: List<PlaceKnowsAboutDto>): List<ServiceGroup> {
    val config = mapOf(
        "accessibility" to Pair("♿", "Erişilebilirlik"),
        "services" to Pair("🛎️", "Hizmetler"),
        "highlights" to Pair("⭐", "Öne Çıkanlar"),
        "payment" to Pair("💳", "Ödeme & Fiyat"),
        "family" to Pair("👨‍👩‍👧", "Aile & Çocuk"),
        "transport" to Pair("🚗", "Ulaşım & Park"),
        "pet" to Pair("🐾", "Evcil Hayvan"),
        "food" to Pair("🍽️", "Yemek Seçenekleri"),
        "offered" to Pair("🎁", "Sunulanlar"),
        "popular" to Pair("🔥", "Popüler Olma Nedeni")
    )
    return knowsAbout.groupBy { it.category }.mapNotNull { (category, items) ->
        val info = config[category] ?: return@mapNotNull null
        ServiceGroup(
            key = category,
            icon = info.first,
            title = info.second,
            items = items.map { it.value }
        )
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
