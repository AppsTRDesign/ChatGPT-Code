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
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.GridItemSpan
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
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
import com.guidexy.app.ui.AppStateViewModel
import com.guidexy.app.ui.components.GridPlaceCard

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun HomeScreen(viewModel: AppStateViewModel, navController: NavController) {
    val latest by viewModel.latestPlaces.collectAsState()
    val popular by viewModel.popularPlaces.collectAsState()
    val mostViewed by viewModel.mostViewedPlaces.collectAsState()
    val categories by viewModel.categories.collectAsState()
    val showAllCategories = remember { mutableStateOf(false) }

    LazyVerticalGrid(
        columns = GridCells.Fixed(2),
        modifier = Modifier.fillMaxSize().padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
        horizontalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        item(span = { GridItemSpan(maxLineSpan) }) {
            Text("Keşfet", style = MaterialTheme.typography.headlineMedium)
        }
        if (categories.isNotEmpty()) {
            item(span = { GridItemSpan(maxLineSpan) }) {
                Text("Kategoriler", style = MaterialTheme.typography.titleLarge)
            }
            item(span = { GridItemSpan(maxLineSpan) }) {
                LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    items(categories.take(8)) { category ->
                        CategoryChip(category = category) {
                            val name = category.business_type ?: "Kategori"
                            val total = category.total ?: 0
                            navController.navigate("category_places/${category.category_slug}?name=${android.net.Uri.encode(name)}&total=$total")
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
            item(span = { GridItemSpan(maxLineSpan) }) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Text("Popüler İşletmeler", style = MaterialTheme.typography.titleLarge)
                    TextButton(onClick = { navController.navigate("popular_list") }) {
                        Text("Tümünü Gör")
                    }
                }
            }
            items(popular) { place ->
                GridPlaceCard(place = place, onClick = { navController.navigate("place/${place.id}") })
            }
        }
        if (latest.isNotEmpty()) {
            item(span = { GridItemSpan(maxLineSpan) }) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Text("Son Eklenenler", style = MaterialTheme.typography.titleLarge)
                    TextButton(onClick = { navController.navigate("latest_list") }) {
                        Text("Tümünü Gör")
                    }
                }
            }
            items(latest) { place ->
                GridPlaceCard(place = place, onClick = { navController.navigate("place/${place.id}") })
            }
        }
        if (mostViewed.isNotEmpty()) {
            item(span = { GridItemSpan(maxLineSpan) }) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Text("En Çok Görüntülenenler", style = MaterialTheme.typography.titleLarge)
                    TextButton(onClick = { navController.navigate("most_viewed_list") }) {
                        Text("Tümünü Gör")
                    }
                }
            }
            items(mostViewed) { place ->
                GridPlaceCard(place = place, onClick = { navController.navigate("place/${place.id}") })
            }
        }
        item(span = { GridItemSpan(maxLineSpan) }) { Spacer(modifier = Modifier.height(32.dp)) }
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
                                val name = category.business_type ?: "Kategori"
                                val total = category.total ?: 0
                                navController.navigate("category_places/${category.category_slug}?name=${android.net.Uri.encode(name)}&total=$total")
                            }
                            .padding(vertical = 8.dp)
                    )
                }
            }
        }
    }

    Spacer(modifier = Modifier.height(0.dp))
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
