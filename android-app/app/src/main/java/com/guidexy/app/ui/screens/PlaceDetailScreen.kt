package com.guidexy.app.ui.screens

import androidx.compose.foundation.Image
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
import androidx.compose.material3.Card
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Tab
import androidx.compose.material3.TabRow
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.ui.Modifier
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.unit.dp
import coil.compose.rememberAsyncImagePainter
import com.guidexy.app.data.ApiClient
import com.guidexy.app.data.PlaceDetailResponse
import com.guidexy.app.data.UserReviewDto
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PlaceDetailScreen(placeId: Long) {
    val detailState = remember { mutableStateOf<PlaceDetailResponse?>(null) }
    val tabState = remember { mutableStateOf(0) }
    val tabs = listOf("Genel", "Yorumlar", "Saatler", "Yoğun", "Hizmetler")

    LaunchedEffect(placeId) {
        val response = withContext(Dispatchers.IO) { ApiClient.service.placeDetail(placeId) }
        detailState.value = response
    }

    val detail = detailState.value ?: return
    val place = detail.place

    Column(modifier = Modifier.fillMaxSize()) {
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
                        Text(place.description ?: "Açıklama yok")
                        Spacer(modifier = Modifier.height(8.dp))
                        Text("Web: ${place.website ?: "-"}")
                        Text("Telefon: ${place.formatted_phone_number ?: "-"}")
                    }
                }
                1 -> {
                    val reviews = detail.user_reviews
                    items(reviews) { review ->
                        ReviewCard(review)
                    }
                }
                2 -> {
                    items(detail.hours) { hour ->
                        Text("Gün ${hour.day}: ${hour.open_time ?: ""} - ${hour.close_time ?: ""}")
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
