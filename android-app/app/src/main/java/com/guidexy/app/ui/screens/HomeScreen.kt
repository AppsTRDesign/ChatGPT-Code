package com.guidexy.app.ui.screens

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
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.rememberModalBottomSheetState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.navigation.NavController
import com.guidexy.app.data.CategoryDto
import com.guidexy.app.data.PlaceDto
import com.guidexy.app.ui.AppStateViewModel
import com.guidexy.app.ui.components.MapPlaceCard
import com.guidexy.app.ui.components.MapSection
import com.guidexy.app.ui.components.PlaceCard

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun HomeScreen(viewModel: AppStateViewModel, navController: NavController) {
    val latest by viewModel.latestPlaces.collectAsState()
    val popular by viewModel.popularPlaces.collectAsState()
    val categories by viewModel.categories.collectAsState()
    val showAllCategories = remember { mutableStateOf(false) }
    val selectedMarkerPlace = remember { mutableStateOf<PlaceDto?>(null) }

    LazyColumn(
        modifier = Modifier.fillMaxSize().padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp)
    ) {
        item {
            Text("Keşfet", style = MaterialTheme.typography.headlineMedium)
        }
        item {
            val mapPlaces = if (popular.isNotEmpty()) popular else latest
            Text("Harita Üzerinde Keşfet", style = MaterialTheme.typography.titleLarge)
            Card {
                MapSection(
                    places = mapPlaces,
                    onMarkerSelected = { place -> selectedMarkerPlace.value = place },
                    height = 320.dp
                )
            }
        }
        item {
            val mapPlaces = if (popular.isNotEmpty()) popular else latest
            if (mapPlaces.isNotEmpty()) {
                LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    items(mapPlaces.take(10)) { place ->
                        MapPlaceCard(place = place, onClick = { navController.navigate("place/${place.id}") })
                    }
                }
            }
        }
        if (categories.isNotEmpty()) {
            item {
                Text("Kategoriler", style = MaterialTheme.typography.titleLarge)
                LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    items(categories.take(5)) { category ->
                        CategoryChip(category = category) {
                            navController.navigate("categories")
                        }
                    }
                    item {
                        TextButton(onClick = { showAllCategories.value = true }) {
                            Text("Hepsini Gör")
                        }
                    }
                }
            }
        }
        if (popular.isNotEmpty()) {
            item { Text("Popüler İşletmeler", style = MaterialTheme.typography.titleLarge) }
            items(popular) { place ->
                PlaceCard(place = place, onClick = { navController.navigate("place/${place.id}") })
            }
        }
        if (latest.isNotEmpty()) {
            item { Text("Son Eklenenler", style = MaterialTheme.typography.titleLarge) }
            items(latest) { place ->
                PlaceCard(place = place, onClick = { navController.navigate("place/${place.id}") })
            }
        }
        item { Spacer(modifier = Modifier.height(32.dp)) }
    }

    if (showAllCategories.value) {
        val sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true)
        ModalBottomSheet(
            onDismissRequest = { showAllCategories.value = false },
            sheetState = sheetState
        ) {
            Column(
                modifier = Modifier.padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                Text("Tüm Kategoriler", style = MaterialTheme.typography.titleLarge)
                categories.forEach { category ->
                    Text(
                        text = category.business_type ?: "Kategori",
                        modifier = Modifier
                            .fillMaxWidth()
                            .clickable {
                                showAllCategories.value = false
                                navController.navigate("categories")
                            }
                            .padding(vertical = 8.dp)
                    )
                }
            }
        }
    }

    selectedMarkerPlace.value?.let { place ->
        val sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true)
        ModalBottomSheet(
            onDismissRequest = { selectedMarkerPlace.value = null },
            sheetState = sheetState
        ) {
            Column(
                modifier = Modifier.padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                Text(place.name, style = MaterialTheme.typography.titleLarge)
                Text(place.formatted_address ?: "")
                Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                    Button(onClick = { navController.navigate("place/${place.id}") }) {
                        Text("Detay")
                    }
                }
            }
        }
    }
}

@Composable
private fun CategoryChip(category: CategoryDto, onClick: () -> Unit) {
    Card(
        modifier = Modifier.clickable { onClick() }
    ) {
        Column(modifier = Modifier.padding(horizontal = 12.dp, vertical = 8.dp)) {
            Text(category.business_type ?: "Kategori", style = MaterialTheme.typography.bodyMedium)
            Text("${category.total} işletme", style = MaterialTheme.typography.labelSmall)
        }
    }
}

@Composable
