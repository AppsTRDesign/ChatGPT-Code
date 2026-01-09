package com.guidexy.app.ui.screens

import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.Image
import androidx.compose.foundation.clickable
import android.text.method.LinkMovementMethod
import android.widget.TextView
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Tab
import androidx.compose.material3.TabRow
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
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
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

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
        TabRow(selectedTabIndex = tabState.value) {
            tabs.forEachIndexed { index, label ->
                Tab(selected = tabState.value == index, onClick = { tabState.value = index }, text = { Text(label) })
            }
        }
        LazyColumn(modifier = Modifier.fillMaxSize().padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
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
                    if (googleReviews.isNotEmpty()) {
                        item {
                            Text("Google Yorumları", style = MaterialTheme.typography.titleMedium)
                        }
                        items(googleReviews) { review ->
                            GoogleReviewCard(review)
                        }
                    }
                    if (detail.user_reviews.isNotEmpty()) {
                        item {
                            Text("Kullanıcı Yorumları", style = MaterialTheme.typography.titleMedium)
                        }
                        items(detail.user_reviews) { review ->
                            ReviewCard(review)
                        }
                    }
                    if (googleReviews.isEmpty() && detail.user_reviews.isEmpty()) {
                        item { Text("Henüz yorum yok.") }
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
private fun ReviewCard(review: UserReviewDto) {
    Card {
        Column(modifier = Modifier.padding(12.dp)) {
            Text(review.author_name ?: "Yorumcu", style = MaterialTheme.typography.titleMedium)
            Text("Puan: ${review.rating ?: "-"}")
            Text(review.review_text ?: "")
        }
    }
}

@Composable
private fun GoogleReviewCard(review: GoogleReviewDto) {
    Card {
        Column(modifier = Modifier.padding(12.dp)) {
            Text(review.author_name ?: "Yorumcu", style = MaterialTheme.typography.titleMedium)
            Text("Puan: ${review.rating ?: "-"}")
            Text(review.relative_time ?: "", style = MaterialTheme.typography.labelSmall)
            Text(review.text ?: "")
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
    val context = LocalContext.current
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
