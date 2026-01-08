package com.guidexy.app.ui.screens

import androidx.compose.foundation.Image
import androidx.compose.foundation.clickable
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
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.ui.Modifier
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.unit.dp
import androidx.navigation.NavController
import coil.compose.rememberAsyncImagePainter
import com.guidexy.app.data.PlaceDto
import com.guidexy.app.ui.AppStateViewModel
import com.google.android.gms.maps.model.LatLng
import com.google.maps.android.compose.GoogleMap
import com.google.maps.android.compose.Marker
import com.google.maps.android.compose.rememberCameraPositionState

@Composable
fun HomeScreen(viewModel: AppStateViewModel, navController: NavController) {
    val latest by viewModel.latestPlaces.collectAsState()
    val popular by viewModel.popularPlaces.collectAsState()
    val queryState = remember { mutableStateOf("") }

    LazyColumn(
        modifier = Modifier.fillMaxSize().padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp)
    ) {
        item {
            Text("Keşfet", style = MaterialTheme.typography.headlineMedium)
            OutlinedTextField(
                value = queryState.value,
                onValueChange = { queryState.value = it },
                label = { Text("İşletme ara") },
                modifier = Modifier.fillMaxWidth().padding(top = 12.dp)
            )
        }
        item {
            val center = LatLng(41.015137, 28.97953)
            val cameraState = rememberCameraPositionState {
                position = com.google.android.gms.maps.model.CameraPosition.fromLatLngZoom(center, 11f)
            }
            Card {
                GoogleMap(
                    modifier = Modifier.fillMaxWidth().height(240.dp),
                    cameraPositionState = cameraState
                ) {
                    latest.take(50).forEach { place ->
                        val lat = place.latitude?.toDoubleOrNull() ?: return@forEach
                        val lng = place.longitude?.toDoubleOrNull() ?: return@forEach
                        Marker(
                            position = LatLng(lat, lng),
                            title = place.name
                        )
                    }
                }
            }
        }
        if (latest.isNotEmpty()) {
            item {
                Text("Son Eklenen İşletmeler", style = MaterialTheme.typography.titleLarge)
            }
            items(latest) { place ->
                PlaceCard(place = place, onClick = { navController.navigate("place/${place.id}") })
            }
        }
        if (popular.isNotEmpty()) {
            item {
                Text("Popüler İşletmeler", style = MaterialTheme.typography.titleLarge)
            }
            items(popular) { place ->
                PlaceCard(place = place, onClick = { navController.navigate("place/${place.id}") })
            }
        }
    }
}

@Composable
private fun PlaceCard(place: PlaceDto, onClick: () -> Unit) {
    Card(modifier = Modifier.fillMaxWidth().clickable { onClick() }) {
        Row(modifier = Modifier.padding(12.dp)) {
            Image(
                painter = rememberAsyncImagePainter(place.business_image),
                contentDescription = place.name,
                modifier = Modifier.width(80.dp).height(80.dp),
                contentScale = ContentScale.Crop
            )
            Spacer(modifier = Modifier.width(12.dp))
            Column(modifier = Modifier.weight(1f)) {
                Text(place.name, style = MaterialTheme.typography.titleMedium)
                Text(place.formatted_address ?: "", style = MaterialTheme.typography.bodySmall)
                Text("Puan: ${place.rating ?: "-"}", style = MaterialTheme.typography.bodySmall)
                Text(place.business_type ?: "", style = MaterialTheme.typography.bodySmall)
            }
        }
    }
}
