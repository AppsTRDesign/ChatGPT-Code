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
import androidx.compose.material3.Card
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.navigation.NavController
import com.guidexy.app.data.ApiClient
import com.guidexy.app.data.PlaceDto
import com.guidexy.app.ui.AppStateViewModel
import com.guidexy.app.ui.components.PlaceCard
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

@Composable
fun CategoryScreen(viewModel: AppStateViewModel, navController: NavController) {
    val categoriesState = viewModel.categories.collectAsState()
    val placesState = remember { mutableStateOf<List<PlaceDto>>(emptyList()) }
    val selectedCity = viewModel.selectedCity.collectAsState().value
    val selectedCountry = viewModel.selectedCountry.collectAsState().value
    val scope = rememberCoroutineScope()

    Column(
        modifier = Modifier.fillMaxSize().padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        Text("Kategoriler", style = MaterialTheme.typography.headlineSmall)
        if (categoriesState.value.isEmpty()) {
            Text("Kategori bulunamadı.")
        }
        LazyColumn(verticalArrangement = Arrangement.spacedBy(8.dp)) {
            items(categoriesState.value) { category ->
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
                                        val places = loadPlaces(
                                            selectedCity?.slug,
                                            category.category_slug ?: "",
                                            selectedCountry?.code
                                        )
                                        placesState.value = places
                                    }
                                }
                        )
                    }
                }
            }
        }
        if (placesState.value.isNotEmpty()) {
            Spacer(modifier = Modifier.height(12.dp))
            Text("Kategori İşletmeleri", style = MaterialTheme.typography.titleMedium)
            LazyColumn(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                items(placesState.value) { place ->
                    PlaceCard(place = place, onClick = { navController.navigate("place/${place.id}") })
                }
            }
        }
    }
}

private suspend fun loadPlaces(city: String?, category: String, country: String?): List<PlaceDto> {
    return withContext(Dispatchers.IO) {
        ApiClient.service.places(city, category, country).places
    }
}
