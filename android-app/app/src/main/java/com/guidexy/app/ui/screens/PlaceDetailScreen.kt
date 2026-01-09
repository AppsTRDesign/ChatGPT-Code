package com.guidexy.app.ui.screens

import android.text.method.LinkMovementMethod
import android.widget.TextView
import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.Image
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
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Tab
import androidx.compose.material3.TabRow
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
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp
import androidx.core.text.HtmlCompat
import androidx.compose.ui.viewinterop.AndroidView
import coil.compose.rememberAsyncImagePainter
import com.guidexy.app.data.ApiClient
import com.guidexy.app.data.GoogleReviewDto
import com.guidexy.app.data.PlaceDetailResponse
import com.guidexy.app.data.UserReviewDto
import com.google.gson.Gson
import com.google.gson.reflect.TypeToken
import kotlinx.coroutines.flow.distinctUntilChanged
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import java.time.LocalDateTime
import java.time.ZoneId
import java.time.format.DateTimeFormatter

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PlaceDetailScreen(placeId: Long, onBack: () -> Unit) {
    val detailState = remember { mutableStateOf<PlaceDetailResponse?>(null) }
    val errorState = remember { mutableStateOf<String?>(null) }
    val loadingState = remember { mutableStateOf(true) }
    val tabState = remember { mutableStateOf(0) }
    val tabs = listOf("Genel", "Yorumlar", "Saatler", "Yoğun", "Hizmetler")
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

    LaunchedEffect(listState, sortedReviews.size, tabState.value) {
        snapshotFlow { listState.layoutInfo.visibleItemsInfo.lastOrNull()?.index }
            .distinctUntilChanged()
            .collect { index ->
                if (tabState.value != 1) {
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
            Row(modifier = Modifier.padding(16.dp)) {
                Image(
                    painter = rememberAsyncImagePainter(place.business_image),
                    contentDescription = place.name,
                    modifier = Modifier.width(96.dp).height(96.dp),
                    contentScale = ContentScale.Crop
                )
                Spacer(modifier = Modifier.width(12.dp))
                Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
                    Text(place.name, style = MaterialTheme.typography.titleLarge)
                    Text(place.formatted_address ?: "", style = MaterialTheme.typography.bodySmall)
                    Text("Puan: ${place.rating ?: "-"}")
                    Text(place.business_type ?: "")
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
        }
        if (detail.gallery_images.isNotEmpty()) {
            LazyRow(
                modifier = Modifier.padding(top = 12.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                items(detail.gallery_images) { image ->
                    Card(modifier = Modifier.width(180.dp).height(120.dp)) {
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
        TabRow(selectedTabIndex = tabState.value) {
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
                        Text("Web: ${place.website ?: "-"}")
                        Text("Telefon: ${place.formatted_phone_number ?: "-"}")
                        Spacer(modifier = Modifier.height(12.dp))
                        HorizontalDivider()
                        Spacer(modifier = Modifier.height(12.dp))
                        Text("Öne Çıkanlar", style = MaterialTheme.typography.titleMedium)
                        detail.knows_about.take(10).forEach { item ->
                            Text(
                                text = "• ${item.value}",
                                style = MaterialTheme.typography.labelMedium,
                                modifier = Modifier.padding(vertical = 4.dp)
                            )
                        }
                    }
                }
                1 -> {
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
                            ReviewCard(review)
                        }
                        if (visibleReviews.size < sortedReviews.size) {
                            item {
                                Text("Daha fazla yorum yükleniyor...", style = MaterialTheme.typography.labelMedium)
                            }
                        }
                    }
                }
                2 -> {
                    items(detail.hours) { hour ->
                        val dayLabel = dayLabel(hour.day)
                        val statusText = when {
                            hour.is_closed == 1 -> "Kapalı"
                            hour.is_24h == 1 -> "24 saat açık"
                            hour.open_time.isNullOrBlank() && hour.close_time.isNullOrBlank() -> "-"
                            else -> "${hour.open_time ?: ""} - ${hour.close_time ?: ""}"
                        }
                        Text("$dayLabel: $statusText")
                    }
                }
                3 -> {
                    item {
                        Text("Yoğun saatler tablosu burada görünecek.")
                    }
                }
                4 -> {
                    items(detail.services) { service ->
                        Text(service.title)
                    }
                }
            }
        }
    }
}

@Composable
private fun ReviewCard(review: CombinedReview) {
    Card {
        Column(modifier = Modifier.padding(12.dp)) {
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                ReviewAvatar(review)
                Column {
                    Text(review.authorName ?: "Yorumcu", style = MaterialTheme.typography.titleMedium)
                    Text("Puan: ${review.rating ?: "-"}")
                    review.createdAt?.let { Text(it, style = MaterialTheme.typography.labelSmall) }
                }
            }
            review.text?.let { Text(it) }
            if (review.textExtra.isNotEmpty()) {
                Spacer(modifier = Modifier.height(8.dp))
                review.textExtra.forEach { (key, value) ->
                    Text("$key: $value", style = MaterialTheme.typography.labelSmall)
                }
            }
            if (review.photoUrls.isNotEmpty()) {
                Spacer(modifier = Modifier.height(8.dp))
                LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    items(review.photoUrls) { url ->
                        Card(modifier = Modifier.width(120.dp).height(80.dp)) {
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

@Composable
private fun HtmlText(html: String) {
    AndroidView(
        factory = {
            TextView(it).apply {
                movementMethod = LinkMovementMethod.getInstance()
                textSize = 14f
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
