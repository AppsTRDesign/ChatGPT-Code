package com.guidexy.app.ui.screens

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.material3.Card
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.snapshotFlow
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.navigation.NavController
import com.guidexy.app.data.ApiClient
import com.guidexy.app.data.CategoryDto
import com.guidexy.app.data.PlaceDto
import com.guidexy.app.ui.AppStateViewModel
import com.guidexy.app.ui.components.PlaceCard
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.distinctUntilChanged
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

@Composable
fun CategoryScreen(viewModel: AppStateViewModel, navController: NavController, initialCategorySlug: String?) {
    val categoriesState = viewModel.categories.collectAsState()
    val placesState = remember { mutableStateOf<List<PlaceDto>>(emptyList()) }
    val selectedCategory = remember { mutableStateOf<CategoryDto?>(null) }
    val loadingState = remember { mutableStateOf(false) }
    val searchQuery = remember { mutableStateOf("") }
    val visibleCount = remember { mutableStateOf(12) }
    val listState = rememberLazyListState()
    val selectedCity = viewModel.selectedCity.collectAsState().value
    val selectedCountry = viewModel.selectedCountry.collectAsState().value
    val scope = rememberCoroutineScope()

    val filteredCategories = remember(categoriesState.value, searchQuery.value) {
        val query = searchQuery.value.trim().lowercase()
        if (query.isBlank()) {
            categoriesState.value
        } else {
            categoriesState.value.filter { category ->
                category.business_type?.lowercase()?.contains(query) == true
            }
        }
    }
    val visibleCategories = remember(filteredCategories, visibleCount.value) {
        filteredCategories.take(visibleCount.value)
    }

    LaunchedEffect(initialCategorySlug, categoriesState.value) {
        val slug = initialCategorySlug ?: return@LaunchedEffect
        val match = categoriesState.value.firstOrNull { it.category_slug == slug }
        if (match != null) {
            loadingState.value = true
            selectedCategory.value = match
            placesState.value = loadPlaces(selectedCity?.slug, slug, selectedCountry?.code)
            loadingState.value = false
        }
    }

    LaunchedEffect(searchQuery.value) {
        visibleCount.value = 12
    }

    LaunchedEffect(listState, visibleCategories.size) {
        snapshotFlow { listState.layoutInfo.visibleItemsInfo.lastOrNull()?.index }
            .distinctUntilChanged()
            .collect { index ->
                if (index != null && index >= visibleCategories.size - 2 && visibleCount.value < filteredCategories.size) {
                    visibleCount.value = (visibleCount.value + 12).coerceAtMost(filteredCategories.size)
                }
            }
    }

    LazyColumn(
        state = listState,
        modifier = Modifier.fillMaxSize().padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        item { Text("Kategoriler", style = MaterialTheme.typography.headlineSmall) }
        item {
            OutlinedTextField(
                value = searchQuery.value,
                onValueChange = { searchQuery.value = it },
                label = { Text("Kategori ara") },
                modifier = Modifier.fillMaxWidth(),
                singleLine = true
            )
        }
        if (categoriesState.value.isEmpty()) {
            item { Text("Kategori bulunamadı.") }
        } else {
            items(visibleCategories) { category ->
                Card(
                    modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)
                ) {
                    Column(
                        modifier = Modifier.padding(12.dp)
                    ) {
                        Text(category.business_type ?: "Kategori", style = MaterialTheme.typography.titleMedium)
                        Text("Toplam: ${category.total}")
                        Spacer(modifier = Modifier.height(8.dp))
                        Text(
                            text = "İşletmeleri Gör",
                            color = MaterialTheme.colorScheme.primary,
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(vertical = 4.dp)
                                .clickable {
                                    scope.launch {
                                        loadingState.value = true
                                        selectedCategory.value = category
                                        val places = loadPlaces(
                                            selectedCity?.slug,
                                            category.category_slug ?: "",
                                            selectedCountry?.code
                                        )
                                        placesState.value = places
                                        loadingState.value = false
                                    }
                                }
                        )
                    }
                }
            }
        }
        if (visibleCategories.size < filteredCategories.size) {
            item { Text("Daha fazla kategori yükleniyor...") }
        }
        if (loadingState.value) {
            item { Text("İşletmeler yükleniyor...") }
        }
        if (placesState.value.isNotEmpty()) {
            item {
                val title = selectedCategory.value?.business_type ?: "Kategori İşletmeleri"
                Text(title, style = MaterialTheme.typography.titleMedium)
            }
            items(placesState.value) { place ->
                PlaceCard(place = place, onClick = { navController.navigate("place/${place.id}") })
            }
        }
    }
}

private suspend fun loadPlaces(city: String?, category: String, country: String?): List<PlaceDto> {
    return withContext(Dispatchers.IO) {
        ApiClient.service.places(city, category, country).places
    }
}
