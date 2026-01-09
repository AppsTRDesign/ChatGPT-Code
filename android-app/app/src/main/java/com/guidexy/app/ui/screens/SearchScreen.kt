package com.guidexy.app.ui.screens

import android.Manifest
import android.content.pm.PackageManager
import android.widget.Toast
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.ExposedDropdownMenu
import androidx.compose.material3.ExposedDropdownMenuBox
import androidx.compose.material3.ExposedDropdownMenuDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.rememberModalBottomSheetState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.snapshotFlow
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp
import androidx.core.content.ContextCompat
import androidx.navigation.NavController
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Search
import com.google.android.gms.location.LocationServices
import com.guidexy.app.data.CategoryDto
import com.guidexy.app.data.PlaceDto
import com.guidexy.app.ui.AppStateViewModel
import com.guidexy.app.ui.components.MapSection
import com.guidexy.app.ui.components.PlaceCard
import kotlinx.coroutines.flow.distinctUntilChanged

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SearchScreen(viewModel: AppStateViewModel, navController: NavController) {
    val context = LocalContext.current
    val categories by viewModel.categories.collectAsState()
    val cities by viewModel.cities.collectAsState()
    val selectedCountry by viewModel.selectedCountry.collectAsState()
    val selectedCity by viewModel.selectedCity.collectAsState()
    val searchResults by viewModel.searchResults.collectAsState()
    val searchHasMore by viewModel.searchHasMore.collectAsState()
    val searchLoading by viewModel.searchLoading.collectAsState()
    val searchTotal by viewModel.searchTotal.collectAsState()
    val searchPage by viewModel.searchPage.collectAsState()
    val perPage = 20

    val queryState = remember { mutableStateOf("") }
    val radiusKmState = remember { mutableStateOf("5") }
    val selectedCategory = remember { mutableStateOf<CategoryDto?>(null) }
    val cityExpanded = remember { mutableStateOf(false) }
    val categoryExpanded = remember { mutableStateOf(false) }
    val viewMode = remember { mutableStateOf(SearchViewMode.Map) }
    val selectedMarkerPlace = remember { mutableStateOf<PlaceDto?>(null) }
    val searchTriggered = remember { mutableStateOf(false) }
    val showFilters = remember { mutableStateOf(false) }
    val listState = rememberLazyListState()

    val requestLocationPermission = rememberLauncherForActivityResult(
        ActivityResultContracts.RequestPermission()
    ) { granted ->
        if (!granted) {
            Toast.makeText(context, "Konum izni verilmedi.", Toast.LENGTH_SHORT).show()
        }
    }

    LaunchedEffect(searchResults.size) {
        if (searchTriggered.value) {
            Toast.makeText(
                context,
                "Arama tamamlandı: ${searchResults.size} sonuç",
                Toast.LENGTH_SHORT
            ).show()
            searchTriggered.value = false
        }
    }

    LaunchedEffect(listState, searchResults.size, viewMode.value) {
        snapshotFlow { listState.layoutInfo.visibleItemsInfo.lastOrNull()?.index }
            .distinctUntilChanged()
            .collect { index ->
                if (viewMode.value != SearchViewMode.List) return@collect
                if (index != null && index >= searchResults.size - 2 && searchHasMore && !searchLoading) {
                    viewModel.loadMoreSearch()
                }
            }
    }

    Column(
        modifier = Modifier.fillMaxSize().padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp)
    ) {
        Text("Ara", style = MaterialTheme.typography.headlineMedium)
        Card(modifier = Modifier.fillMaxWidth()) {
            Column(
                modifier = Modifier.padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    OutlinedTextField(
                        value = queryState.value,
                        onValueChange = { queryState.value = it },
                        label = { Text("İşletme veya anahtar kelime") },
                        modifier = Modifier.weight(1f),
                        singleLine = true
                    )
                    IconButton(
                        onClick = {
                            searchTriggered.value = true
                            viewModel.searchPlaces(
                                query = queryState.value.takeIf { it.isNotBlank() },
                                category = selectedCategory.value?.category_slug,
                                city = selectedCity?.slug,
                                country = selectedCountry?.code
                            )
                        }
                    ) {
                        Icon(Icons.Default.Search, contentDescription = "Ara")
                    }
                }
                Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                    Button(onClick = { showFilters.value = true }) {
                        Text("Detaylı Ara")
                    }
                    TextButton(onClick = {
                        viewMode.value = if (viewMode.value == SearchViewMode.Map) {
                            SearchViewMode.List
                        } else {
                            SearchViewMode.Map
                        }
                    }) {
                        Text(if (viewMode.value == SearchViewMode.Map) "Liste" else "Harita")
                    }
                }
            }
        }
        Card(modifier = Modifier.fillMaxWidth()) {
            MapSection(
                places = searchResults,
                onMarkerSelected = { place -> selectedMarkerPlace.value = place },
                height = if (viewMode.value == SearchViewMode.Map) 420.dp else 220.dp
            )
        }
        if (viewMode.value == SearchViewMode.List) {
            LazyColumn(
                state = listState,
                verticalArrangement = Arrangement.spacedBy(12.dp),
                modifier = Modifier.fillMaxSize()
            ) {
                if (searchResults.isEmpty()) {
                    item { Text("Sonuç bulunamadı.") }
                } else {
                    item {
                        val totalPages = if (searchTotal > 0) {
                            kotlin.math.ceil(searchTotal / perPage.toDouble()).toInt()
                        } else {
                            1
                        }
                        Text(
                            "Arama Sonuçları (${searchResults.size}/${searchTotal.takeIf { it > 0 } ?: searchResults.size}) • Sayfa $searchPage/$totalPages",
                            style = MaterialTheme.typography.titleLarge
                        )
                    }
                    items(searchResults) { place ->
                        PlaceCard(place = place, onClick = { navController.navigate("place/${place.id}") })
                    }
                    if (searchLoading) {
                        item { Text("Yeni sayfa yükleniyor...") }
                    } else if (!searchHasMore) {
                        item { Text("Tüm sayfalar yüklendi.") }
                    }
                }
                item { Spacer(modifier = Modifier.height(32.dp)) }
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
                place.distance_m?.let { distance ->
                    Text("Uzaklık: ${"%.1f".format(distance / 1000.0)} km")
                }
                Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                    Button(onClick = { navController.navigate("place/${place.id}") }) {
                        Text("Detay")
                    }
                }
            }
        }
    }

    if (showFilters.value) {
        val sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true)
        ModalBottomSheet(
            onDismissRequest = { showFilters.value = false },
            sheetState = sheetState
        ) {
            LazyColumn(
                modifier = Modifier.fillMaxWidth().padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                item { Text("Detaylı Arama", style = MaterialTheme.typography.titleLarge) }
                item {
                    ExposedDropdownMenuBox(
                        expanded = cityExpanded.value,
                        onExpandedChange = { cityExpanded.value = !cityExpanded.value }
                    ) {
                        OutlinedTextField(
                            value = selectedCity?.city?.ifBlank { "Tümü" } ?: "Tümü",
                            onValueChange = {},
                            label = { Text("Şehir") },
                            modifier = Modifier.menuAnchor().fillMaxWidth(),
                            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = cityExpanded.value) },
                            readOnly = true
                        )
                        ExposedDropdownMenu(
                            expanded = cityExpanded.value,
                            onDismissRequest = { cityExpanded.value = false }
                        ) {
                            androidx.compose.material3.DropdownMenuItem(
                                text = { Text("Tümü") },
                                onClick = {
                                    viewModel.clearCitySelection()
                                    cityExpanded.value = false
                                }
                            )
                            cities.forEach { city ->
                                androidx.compose.material3.DropdownMenuItem(
                                    text = { Text(city.city) },
                                    onClick = {
                                        viewModel.selectCity(city)
                                        cityExpanded.value = false
                                    }
                                )
                            }
                        }
                    }
                }
                item {
                    ExposedDropdownMenuBox(
                        expanded = categoryExpanded.value,
                        onExpandedChange = { categoryExpanded.value = !categoryExpanded.value }
                    ) {
                        OutlinedTextField(
                            value = selectedCategory.value?.business_type ?: "Tümü",
                            onValueChange = {},
                            label = { Text("Kategori") },
                            modifier = Modifier.menuAnchor().fillMaxWidth(),
                            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = categoryExpanded.value) },
                            readOnly = true
                        )
                        ExposedDropdownMenu(
                            expanded = categoryExpanded.value,
                            onDismissRequest = { categoryExpanded.value = false }
                        ) {
                            androidx.compose.material3.DropdownMenuItem(
                                text = { Text("Tümü") },
                                onClick = {
                                    selectedCategory.value = null
                                    categoryExpanded.value = false
                                }
                            )
                            categories.forEach { category ->
                                androidx.compose.material3.DropdownMenuItem(
                                    text = { Text(category.business_type ?: "Kategori") },
                                    onClick = {
                                        selectedCategory.value = category
                                        categoryExpanded.value = false
                                    }
                                )
                            }
                        }
                    }
                }
                item {
                    OutlinedTextField(
                        value = radiusKmState.value,
                        onValueChange = { radiusKmState.value = it },
                        label = { Text("Yakınımda km") },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true
                    )
                }
                item {
                    Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                        Button(
                            onClick = {
                                searchTriggered.value = true
                                showFilters.value = false
                                viewModel.searchPlaces(
                                    query = queryState.value.takeIf { it.isNotBlank() },
                                    category = selectedCategory.value?.category_slug,
                                    city = selectedCity?.slug,
                                    country = selectedCountry?.code
                                )
                            }
                        ) {
                            Text("Ara")
                        }
                        Button(
                            onClick = {
                                val permission = Manifest.permission.ACCESS_FINE_LOCATION
                                if (ContextCompat.checkSelfPermission(context, permission)
                                    != PackageManager.PERMISSION_GRANTED
                                ) {
                                    requestLocationPermission.launch(permission)
                                    return@Button
                                }
                                val fusedLocation = LocationServices.getFusedLocationProviderClient(context)
                                fusedLocation.lastLocation
                                    .addOnSuccessListener { location ->
                                        if (location == null) {
                                            Toast.makeText(context, "Konum alınamadı.", Toast.LENGTH_SHORT).show()
                                        } else {
                                            Toast.makeText(context, "Konum alındı.", Toast.LENGTH_SHORT).show()
                                            searchTriggered.value = true
                                            showFilters.value = false
                                            viewModel.searchPlaces(
                                                query = queryState.value.takeIf { it.isNotBlank() },
                                                category = selectedCategory.value?.category_slug,
                                                city = selectedCity?.slug,
                                                country = selectedCountry?.code,
                                                sort = "distance",
                                                lat = location.latitude,
                                                lng = location.longitude,
                                                radiusKm = radiusKmState.value.toDoubleOrNull()
                                            )
                                        }
                                    }
                                    .addOnFailureListener {
                                        Toast.makeText(context, "Konum alınamadı.", Toast.LENGTH_SHORT).show()
                                    }
                            }
                        ) {
                            Text("Yakınımda")
                        }
                    }
                }
            }
        }
    }
}

private enum class SearchViewMode {
    Map,
    List
}
