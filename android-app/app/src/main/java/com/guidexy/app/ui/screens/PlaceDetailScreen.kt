package com.guidexy.app.ui.screens

import android.content.Context
import android.content.Intent
import android.graphics.BitmapFactory
import android.graphics.drawable.BitmapDrawable
import android.graphics.drawable.Drawable
import android.net.Uri
import android.text.Html
import android.text.method.LinkMovementMethod
import android.util.TypedValue
import android.widget.TextView
import androidx.core.content.FileProvider
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxHeight
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
import androidx.compose.material.icons.filled.ArrowForward
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Call
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Language
import androidx.compose.material.icons.filled.Link
import androidx.compose.material.icons.filled.Schedule
import androidx.compose.material.icons.filled.Share
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.filled.StarBorder
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
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
import androidx.compose.runtime.rememberCoroutineScope
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
import java.net.URL
import java.util.Locale
import kotlin.math.ceil

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
    val reviewsPerPage = PaginationConfig.reviewsPerPage
    val reviewPage = remember { mutableStateOf(1) }
    val reviewLoading = remember { mutableStateOf(false) }
    val combinedReviews = remember(googleReviews, detail.user_reviews) {
        buildCombinedReviews(googleReviews, detail.user_reviews)
    }
    val sortedReviews = remember(combinedReviews, reviewSort.value) {
        sortCombinedReviews(combinedReviews, reviewSort.value)
    }
    val totalReviewPages = remember(sortedReviews.size, reviewsPerPage) {
        ceil(sortedReviews.size / reviewsPerPage.toDouble()).toInt().coerceAtLeast(1)
    }
    val visibleReviews = remember(sortedReviews, reviewPage.value) {
        sortedReviews.take(reviewPage.value * reviewsPerPage)
    }
    val ratingSummary = remember(sortedReviews) { buildRatingSummary(sortedReviews) }
    val reviewPhotos = remember(combinedReviews) { buildReviewPhotos(combinedReviews) }
    val selectedGalleryId = remember(detail.galleries) {
        mutableStateOf(detail.galleries.firstOrNull()?.id)
    }
    val galleryImages = remember(detail.gallery_images, selectedGalleryId.value) {
        detail.gallery_images.filter { it.gallery_id == selectedGalleryId.value }
    }
    val galleryDialogState = remember { mutableStateOf<GalleryDialogState?>(null) }
    val scope = rememberCoroutineScope()

    LaunchedEffect(reviewPage.value, reviewSort.value) {
        if (tabState.value == 2) {
            reviewLoading.value = true
            delay(200)
            reviewLoading.value = false
        }
    }

    LaunchedEffect(sortedReviews.size, reviewsPerPage) {
        val maxPage = ceil(sortedReviews.size / reviewsPerPage.toDouble()).toInt().coerceAtLeast(1)
        if (reviewPage.value > maxPage) {
            reviewPage.value = maxPage
        }
    }

    LaunchedEffect(listState, visibleReviews.size, tabState.value, totalReviewPages) {
        snapshotFlow { listState.layoutInfo.visibleItemsInfo.lastOrNull()?.index }
            .distinctUntilChanged()
            .collect { index ->
                if (tabState.value != 2) return@collect
                if (index != null && index >= visibleReviews.size - 2) {
                    if (!reviewLoading.value && reviewPage.value < totalReviewPages) {
                        reviewPage.value += 1
                    }
                }
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
                        place.current_status?.let { status ->
                            val isOpen = status.status == "open"
                            StatusBadge(status = PlaceStatus(status.text ?: "-", isOpen))
                        }
                    }
                }
                Spacer(modifier = Modifier.height(12.dp))
                val lat = place.latitude?.toDoubleOrNull()
                val lng = place.longitude?.toDoubleOrNull()
                if (lat != null && lng != null) {
                    LazyRow(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        item {
                            Button(onClick = {
                                val encodedName = Uri.encode(place.name)
                                val uri = Uri.parse("geo:$lat,$lng?q=$lat,$lng($encodedName)")
                                val intent = Intent(Intent.ACTION_VIEW, uri)
                                intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                                context.startActivity(intent)
                            }) {
                                Row(
                                    horizontalArrangement = Arrangement.spacedBy(6.dp),
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Icon(Icons.Default.ArrowForward, contentDescription = null)
                                    Text("Yol Tarifi")
                                }
                            }
                        }

                        if (!place.formatted_phone_number.isNullOrBlank()) {
                            item {
                                Button(onClick = {
                                    val intent = Intent(Intent.ACTION_DIAL, Uri.parse("tel:${place.formatted_phone_number}"))
                                    intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                                    context.startActivity(intent)
                                }) {
                                    Row(
                                        horizontalArrangement = Arrangement.spacedBy(6.dp),
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        Icon(Icons.Default.Call, contentDescription = null)
                                        Text("Ara")
                                    }
                                }
                            }
                        }

                        if (!place.website.isNullOrBlank()) {
                            item {
                                Button(onClick = {
                                    val intent = Intent(Intent.ACTION_VIEW, Uri.parse(place.website))
                                    intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                                    context.startActivity(intent)
                                }) {
                                    Row(
                                        horizontalArrangement = Arrangement.spacedBy(6.dp),
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        Icon(Icons.Default.Language, contentDescription = null)
                                        Text("Web")
                                    }
                                }
                            }
                        }

                        item {
                            Button(onClick = {
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

                                        val uri = FileProvider.getUriForFile(
                                            ctx,
                                            "${ctx.packageName}.provider",
                                            file
                                        )

                                        val slug = permalink(place.name)
                                        val shareUrl = "https://guidexy.com/${place.id}-$slug"
                                        val text = "${place.name}\n$shareUrl"

                                        withContext(Dispatchers.Main) {
                                            val shareIntent = Intent(Intent.ACTION_SEND).apply {
                                                type = "image/*"
                                                putExtra(Intent.EXTRA_TEXT, text)
                                                putExtra(Intent.EXTRA_STREAM, uri)
                                                addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
                                            }
                                            val chooser = Intent.createChooser(shareIntent, "Paylaş")
                                            chooser.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                                            ctx.startActivity(chooser)
                                        }
                                    } catch (e: Exception) {
                                        e.printStackTrace()
                                    }
                                }
                            }) {
                                Row(
                                    horizontalArrangement = Arrangement.spacedBy(6.dp),
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Icon(Icons.Default.Share, contentDescription = "Paylaş")
                                    Text("Paylaş")
                                }
                            }
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
        if (tabState.value == 2) {
            Column(modifier = Modifier.fillMaxWidth().padding(horizontal = 16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                Text(
                    "${sortedReviews.size} yorum • ${reviewPage.value}/$totalReviewPages",
                    style = MaterialTheme.typography.labelMedium
                )
                if (reviewLoading.value) {
                    Row(horizontalArrangement = Arrangement.spacedBy(8.dp), verticalAlignment = Alignment.CenterVertically) {
                        CircularProgressIndicator(modifier = Modifier.height(18.dp).width(18.dp), strokeWidth = 2.dp)
                        Text("Yükleniyor...")
                    }
                }
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
                    }
                }
                1 -> {
                    item {
                        if (reviewPhotos.isNotEmpty()) {
                            Text("Kullanıcı Fotoğrafları", style = MaterialTheme.typography.titleMedium)
                            LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                                items(reviewPhotos) { photo ->
                                    ReviewPhotoCard(photo = photo)
                                }
                            }
                            Spacer(modifier = Modifier.height(12.dp))
                        }
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
                            reviewPage.value = 1
                        })
                    }
                    if (sortedReviews.isEmpty()) {
                        item { Text("Henüz yorum yok.") }
                    } else {
                        items(visibleReviews) { review ->
                            ReviewCard(review = review)
                        }
                        if (!reviewLoading.value && reviewPage.value >= totalReviewPages) {
                            item { Text("Tüm yorumlar gösterildi.") }
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
                        Card(modifier = Modifier.fillMaxWidth()) {
                            Row(
                                modifier = Modifier.padding(12.dp),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Row(horizontalArrangement = Arrangement.spacedBy(8.dp), verticalAlignment = Alignment.CenterVertically) {
                                    Icon(Icons.Default.Schedule, contentDescription = null)
                                    Column {
                                        Text(dayLabel, style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.Medium)
                                        Text("Çalışma Saatleri", style = MaterialTheme.typography.labelSmall)
                                    }
                                }
                                Text(statusText, style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.SemiBold)
                            }
                        }
                    }
                }
                4 -> {
                    val busyHours = parseBusyHours(place.busy_hours)
                    if (busyHours.isEmpty()) {
                        item { Text("Yoğunluk verisi bulunamadı.") }
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
                            SocialLinkCard(link = link)
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
                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    review.textExtra.forEach { (key, value) ->
                        ReviewExtraCard(label = key, value = value)
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
private fun ReviewPhotoCard(photo: ReviewPhoto) {
    Card(modifier = Modifier.width(180.dp)) {
        Column(modifier = Modifier.padding(8.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            Image(
                painter = rememberAsyncImagePainter(photo.url),
                contentDescription = null,
                contentScale = ContentScale.Crop,
                modifier = Modifier.fillMaxWidth().height(120.dp)
            )
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp), verticalAlignment = Alignment.CenterVertically) {
                if (!photo.profilePhotoUrl.isNullOrBlank()) {
                    Card(modifier = Modifier.width(32.dp).height(32.dp)) {
                        Image(
                            painter = rememberAsyncImagePainter(photo.profilePhotoUrl),
                            contentDescription = photo.authorName,
                            contentScale = ContentScale.Crop,
                            modifier = Modifier.fillMaxSize()
                        )
                    }
                } else {
                    Card(modifier = Modifier.width(32.dp).height(32.dp)) {
                        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                            Text(text = photo.authorName?.take(1)?.uppercase() ?: "?")
                        }
                    }
                }
                Column {
                    Text(photo.authorName ?: "Kullanıcı", style = MaterialTheme.typography.labelMedium)
                    photo.createdAt?.let { Text(it, style = MaterialTheme.typography.labelSmall) }
                }
            }
        }
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
fun HtmlText(
    html: String,
    modifier: Modifier = Modifier,
    fontSizeSp: Float = 14f,
    lineSpacingExtra: Float = 8f,
    lineSpacingMultiplier: Float = 1.2f,
    maxLines: Int = Int.MAX_VALUE
) {
    AndroidView(
        modifier = modifier,
        factory = { context ->
            TextView(context).apply {
                movementMethod = LinkMovementMethod.getInstance()
                isFocusable = false
                isClickable = false
                setLineSpacing(lineSpacingExtra, lineSpacingMultiplier)
                setTextSize(TypedValue.COMPLEX_UNIT_SP, fontSizeSp)
                this.maxLines = maxLines
                linksClickable = true
            }
        },
        update = { view ->
            val imageGetter = HtmlImageGetter(view, view.context)
            val spanned = HtmlCompat.fromHtml(
                html.trim(),
                HtmlCompat.FROM_HTML_MODE_LEGACY,
                imageGetter,
                null
            )
            view.text = spanned
        }
    )
}

class HtmlImageGetter(
    private val textView: TextView,
    private val context: Context
) : Html.ImageGetter {
    override fun getDrawable(source: String): Drawable? {
        return try {
            val url = URL(source)
            val connection = url.openConnection()
            connection.connect()
            val input = connection.getInputStream()
            val bitmap = BitmapFactory.decodeStream(input)
            val drawable = BitmapDrawable(context.resources, bitmap)
            drawable.setBounds(0, 0, drawable.intrinsicWidth, drawable.intrinsicHeight)
            drawable
        } catch (e: Exception) {
            e.printStackTrace()
            null
        }
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
            },
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)
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
private fun ReviewExtraCard(label: String, value: String) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant),
        border = androidx.compose.foundation.BorderStroke(1.dp, MaterialTheme.colorScheme.outline)
    ) {
        Row(
            modifier = Modifier.padding(12.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.SpaceBetween
        ) {
            Column(modifier = Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                Text(label, style = MaterialTheme.typography.labelMedium, color = MaterialTheme.colorScheme.primary)
                Text(value, style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.Medium)
            }
        }
    }
}

@Composable
private fun BusyHoursChart(busyHours: Map<String, List<BusyHour>>) {
    val today = dayName(LocalDateTime.now().dayOfWeek.value)
    val rawEntries = busyHours[today] ?: busyHours.values.firstOrNull().orEmpty()
    val entries = buildFullDayHours(rawEntries)
    Card(modifier = Modifier.fillMaxWidth()) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text("Yoğunluk Saatleri ($today)", style = MaterialTheme.typography.titleMedium)
            if (entries.isEmpty()) {
                Text("Veri bulunamadı.")
            } else {
                val max = entries.maxOf { it.value }.coerceAtLeast(1)
                LazyRow(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                    items(entries.size) { index ->
                        val entry = entries[index]
                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                            Text("${entry.value}%", style = MaterialTheme.typography.labelSmall)
                            Box(
                                modifier = Modifier
                                    .width(18.dp)
                                    .height((140 * entry.value / max).coerceAtLeast(8).dp)
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

private data class ReviewPhoto(
    val url: String,
    val authorName: String?,
    val createdAt: String?,
    val profilePhotoUrl: String?
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
    val context = LocalContext.current
    Card(modifier = Modifier.fillMaxWidth()) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text(section.title, style = MaterialTheme.typography.titleMedium)
            Spacer(modifier = Modifier.height(8.dp))
            section.items.forEach { item ->
                Card(
                    modifier = Modifier.fillMaxWidth().padding(vertical = 6.dp),
                    border = androidx.compose.foundation.BorderStroke(1.dp, MaterialTheme.colorScheme.outline)
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
                            Button(
                                onClick = {
                                    val uri = Uri.parse(item.buttonUrl)
                                    val intent = Intent(Intent.ACTION_VIEW, uri)
                                    intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                                    context.startActivity(intent)
                                },
                                enabled = !item.buttonUrl.isNullOrBlank()
                            ) {
                                Icon(Icons.Default.ArrowForward, contentDescription = null)
                                Spacer(modifier = Modifier.width(6.dp))
                                Text(buttonLabel)
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

private fun buildReviewPhotos(reviews: List<CombinedReview>): List<ReviewPhoto> {
    return reviews.flatMap { review ->
        review.photoUrls.filter { it.isNotBlank() }.map { url ->
            ReviewPhoto(
                url = url,
                authorName = review.authorName,
                createdAt = review.createdAt,
                profilePhotoUrl = review.profilePhotoUrl
            )
        }
    }
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
                val hourLabel = parseHourValue(hourValue) ?: return@mapNotNull null
                BusyHour(hour = hourLabel, value = busy)
            }
        }
    }.getOrDefault(emptyMap())
}

private fun permalink(text: String): String {
    var s = text.trim()
    if (s.isEmpty()) return ""

    val trMap = mapOf(
        'Ç' to 'C', 'Ş' to 'S', 'Ğ' to 'G', 'Ü' to 'U', 'İ' to 'I', 'Ö' to 'O',
        'ç' to 'c', 'ş' to 's', 'ğ' to 'g', 'ü' to 'u', 'ı' to 'i', 'ö' to 'o'
    )
    s = buildString {
        for (c in s) append(trMap[c] ?: c)
    }

    s = Normalizer.normalize(s, Normalizer.Form.NFD)
        .replace("[^\\p{ASCII}]".toRegex(), "")

    s = s.lowercase()
    s = s.replace("[^a-z0-9]+".toRegex(), "-")
    s = s.replace("-+".toRegex(), "-")

    return s.trim('-')
}

private fun parseHourValue(value: String): String? {
    val digits = value.trim().takeWhile { it.isDigit() }
    val hour = digits.toIntOrNull() ?: return null
    return String.format("%02d", hour.coerceIn(0, 23))
}

private fun buildFullDayHours(entries: List<BusyHour>): List<BusyHour> {
    if (entries.isEmpty()) return emptyList()
    val lookup = entries.associateBy { it.hour }
    return (0..23).map { hour ->
        val label = String.format("%02d", hour)
        val value = lookup[label]?.value ?: 0
        BusyHour(hour = label, value = value)
    }
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
