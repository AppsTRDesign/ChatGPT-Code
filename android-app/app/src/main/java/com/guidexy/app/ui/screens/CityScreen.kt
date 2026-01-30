package com.guidexy.app.ui.screens

import android.net.Uri
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
import androidx.compose.runtime.snapshotFlow
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.navigation.NavController
import com.guidexy.app.ui.AppStateViewModel
import com.guidexy.app.ui.PaginationConfig
import com.guidexy.app.ui.components.LoadingRow
import kotlinx.coroutines.flow.distinctUntilChanged

@Composable
fun CityScreen(viewModel: AppStateViewModel, navController: NavController) {
    val citiesState = viewModel.cities.collectAsState()
    val searchQuery = remember { mutableStateOf("") }
    val visibleCount = remember { mutableStateOf(PaginationConfig.citiesPerPage) }
    val listState = rememberLazyListState()

    val filteredCities = remember(citiesState.value, searchQuery.value) {
        val query = searchQuery.value.trim().lowercase()
        if (query.isBlank()) {
            citiesState.value
        } else {
            citiesState.value.filter { city ->
                city.city.lowercase().contains(query)
            }
        }
    }

    val visibleCities = remember(filteredCities, visibleCount.value) {
        filteredCities.take(visibleCount.value)
    }

    LaunchedEffect(searchQuery.value) {
        visibleCount.value = PaginationConfig.citiesPerPage
    }

    LaunchedEffect(listState, visibleCities.size) {
        snapshotFlow { listState.layoutInfo.visibleItemsInfo.lastOrNull()?.index }
            .distinctUntilChanged()
            .collect { index ->
                if (index != null && index >= visibleCities.size - 2 && visibleCount.value < filteredCities.size) {
                    visibleCount.value =
                        (visibleCount.value + PaginationConfig.citiesPerPage).coerceAtMost(filteredCities.size)
                }
            }
    }

    LazyColumn(
        state = listState,
        modifier = Modifier.fillMaxSize().padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        item { Text("Şehirler", style = MaterialTheme.typography.headlineSmall) }
        item {
            OutlinedTextField(
                value = searchQuery.value,
                onValueChange = { searchQuery.value = it },
                label = { Text("Şehir ara") },
                modifier = Modifier.fillMaxWidth(),
                singleLine = true
            )
        }
        if (citiesState.value.isEmpty()) {
            item { Text("Şehir bulunamadı.") }
        } else {
            items(visibleCities) { city ->
                Card(
                    modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)
                ) {
                    Column(modifier = Modifier.padding(12.dp)) {
                        Text(city.city, style = MaterialTheme.typography.titleMedium)
                        Text("Toplam: ${city.total_places}")
                        Spacer(modifier = Modifier.height(8.dp))
                        Text(
                            text = "Kategorileri Gör",
                            color = MaterialTheme.colorScheme.primary,
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(vertical = 4.dp)
                                .clickable {
                                    val slug = city.slug
                                    val name = city.city
                                    val total = city.total_places
                                    navController.navigate("city_categories/$slug?name=${Uri.encode(name)}&total=$total")
                                }
                        )
                    }
                }
            }
        }
        if (visibleCities.size < filteredCities.size) {
            item { LoadingRow("Daha fazla şehir yükleniyor...") }
        }
    }
}
