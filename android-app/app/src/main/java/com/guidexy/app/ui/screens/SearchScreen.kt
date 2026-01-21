package com.guidexy.app.ui.screens

import android.Manifest
import android.content.Intent
import android.content.pm.PackageManager
import android.net.Uri
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.Image
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.KeyboardOptions
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.core.content.ContextCompat
import androidx.navigation.NavController
import coil.compose.rememberAsyncImagePainter
import com.google.android.gms.location.LocationServices
import com.guidexy.app.data.ApiClient
import com.guidexy.app.data.CategoryDto
import com.guidexy.app.data.CityDto
import com.guidexy.app.data.PlaceDto
import com.guidexy.app.ui.AppStateViewModel
import com.guidexy.app.ui.PaginationConfig
import com.guidexy.app.ui.PlaceSortOption
import com.guidexy.app.ui.components.MapSection
import com.guidexy.app.ui.components.PlaceCard
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.distinctUntilChanged
import kotlinx.coroutines.withContext

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SearchScreen(viewModel: AppStateViewModel, navController: NavController) {
    val context = LocalContext.current
    val categories by viewModel.categories.collectAsState()
    val cities by viewModel.cities.collectAsState()
    val selectedCountry by viewModel.selectedCountry.collectAsState()
    val searchResults by viewModel.searchResults.collectAsState()
    val searchHasMore by viewModel.searchHasMore.collectAsState()
    val searchLoading by viewModel.searchLoading.collectAsState()
    val searchTotal by viewModel.searchTotal.collectAsState()
    val citySheetOpen = remember { mutableStateOf(false) }
    val categorySheetOpen = remember { mutableStateOf(false) }
    val citySearchQuery = remember { mutableStateOf("") }
    val categorySearchQuery = remember { mutableStateOf("") }

    // States
    val queryState = remember { mutableStateOf("") }
    val radiusKmState = remember { mutableStateOf("5") }
    val selectedCategory = remember { mutableStateOf<CategoryDto?>(null) }
    val selectedCity = remember { mutableStateOf<CityDto?>(null) }
    val viewMode = remember { mutableStateOf(SearchViewMode.List) }
    val selectedMarkerPlace = remember { mutableStateOf<PlaceDto?>(null) }
    val showFilters = remember { mutableStateOf(false) }
    val hasSearched = remember { mutableStateOf(false) } // Arama yapılma durumu
    val mapExpanded = remember { mutableStateOf(false) }
    val lastLocation = remember { mutableStateOf<Pair<Double, Double>?>(null) }
    val lastRadius = remember { mutableStateOf<Double?>(null) }
    val pendingDistanceSearch = remember { mutableStateOf(false) }

    // Map specific states
    val mapPage = remember { mutableIntStateOf(1) }
    val mapTotal = remember { mutableIntStateOf(0) }
    val mapTotalPages = remember { mutableIntStateOf(1) }
    val mapResults = remember { mutableStateOf<List<PlaceDto>>(emptyList()) }
    val mapLoading = remember { mutableStateOf(false) }
    val mapParams = remember { mutableStateOf(MapSearchParams()) }

    val listState = rememberLazyListState()
    val sortOptions = remember {
        listOf(
            PlaceSortOption("new", "En Yeni"),
            PlaceSortOption("old", "En Eski"),
            PlaceSortOption("name_asc", "Ada Göre (A-Z)"),
            PlaceSortOption("name_desc", "Ada Göre (Z-A)"),
            PlaceSortOption("rating_desc", "En Yüksek Puan"),
            PlaceSortOption("distance", "Uzaklığa Göre"),
            PlaceSortOption("views", "En Çok Ziyaret")
        )
    }
    val selectedSort = remember { mutableStateOf(sortOptions.first()) }
    val sortExpanded = remember { mutableStateOf(false) }

    fun performSearch(sortKey: String = selectedSort.value.key) {
        hasSearched.value = true // Arama tıklandığında aktif et
        mapPage.intValue = 1

        mapParams.value = MapSearchParams(
            query = queryState.value.takeIf { it.isNotBlank() },
            category = selectedCategory.value?.category_slug,
            city = selectedCity.value?.slug,
            country = selectedCountry?.code,
            sort = sortKey,
            lat = lastLocation.value?.first,
            lng = lastLocation.value?.second
        )

        viewModel.searchPlaces(
            query = queryState.value.takeIf { it.isNotBlank() },
            category = selectedCategory.value?.category_slug,
            city = selectedCity.value?.slug,
            country = selectedCountry?.code,
            sort = sortKey,
            lat = lastLocation.value?.first,
            lng = lastLocation.value?.second
        )
    }

    val requestLocationPermission = rememberLauncherForActivityResult(
        ActivityResultContracts.RequestPermission()
    ) { granted ->
        if (granted) pendingDistanceSearch.value = true
    }

    val fetchLocationAndSearch = {
        val permission = Manifest.permission.ACCESS_FINE_LOCATION
        if (ContextCompat.checkSelfPermission(context, permission) != PackageManager.PERMISSION_GRANTED) {
            requestLocationPermission.launch(permission)
        } else {
            val fusedLocation = LocationServices.getFusedLocationProviderClient(context)
            fusedLocation.lastLocation.addOnSuccessListener { location ->
                if (location != null) {
                    lastLocation.value = location.latitude to location.longitude
                    lastRadius.value = radiusKmState.value.toDoubleOrNull()
                    selectedSort.value = sortOptions.first { it.key == "distance" }
                    performSearch("distance")
                }
            }
        }
    }

    LaunchedEffect(pendingDistanceSearch.value) {
        if (pendingDistanceSearch.value) {
            pendingDistanceSearch.value = false
            fetchLocationAndSearch()
        }
    }

    fun handleSearch(sortKey: String) {
        if (sortKey == "distance") fetchLocationAndSearch()
        else {
            lastLocation.value = null
            lastRadius.value = null
            performSearch(sortKey)
        }
    }

    LaunchedEffect(listState) {
        snapshotFlow { listState.layoutInfo.visibleItemsInfo.lastOrNull()?.index }
            .distinctUntilChanged()
            .collect { index ->
                if (viewMode.value == SearchViewMode.List &&
                    index != null &&
                    index >= searchResults.size - 2 &&
                    searchHasMore && !searchLoading
                ) {
                    viewModel.loadMoreSearch()
                }
            }
    }

    LaunchedEffect(mapPage.intValue, mapParams.value) {
        if (!hasSearched.value) return@LaunchedEffect
        mapLoading.value = true
        try {
            val params = mapParams.value
            val response = withContext(Dispatchers.IO) {
                ApiClient.service.placesAlternate(
                    mode = "list",
                    query = params.query,
                    categorySlug = params.category,
                    citySlug = params.city,
                    countrySlug = params.country,
                    sort = params.sort,
                    lat = params.lat,
                    lng = params.lng,
                    perPage = PaginationConfig.resultsPerPage,
                    page = mapPage.intValue
                )
            }
            mapResults.value = response.data
            val total = response.total ?: response.data.size
            mapTotal.intValue = total
            mapTotalPages.intValue = kotlin.math.ceil(total / PaginationConfig.resultsPerPage.toDouble()).toInt().coerceAtLeast(1)
        } catch (e: Exception) {
            e.printStackTrace()
        } finally {
            mapLoading.value = false
        }
    }

    Column(
        modifier = Modifier.fillMaxSize().padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp)
    ) {
        // Başlık Düzeltildi
        Text("Arama", style = MaterialTheme.typography.headlineMedium)

        if (!mapExpanded.value) {
            Card(modifier = Modifier.fillMaxWidth()) {
                Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    Row(horizontalArrangement = Arrangement.spacedBy(8.dp), verticalAlignment = Alignment.CenterVertically) {
                        OutlinedTextField(
                            value = queryState.value,
                            onValueChange = { queryState.value = it },
                            placeholder = { Text("Mekan, restoran...") },
                            modifier = Modifier.weight(1f),
                            singleLine = true,
                            keyboardOptions = KeyboardOptions(
                                keyboardType = KeyboardType.Text,
                                imeAction = ImeAction.Search,
                                autoCorrect = true
                            ),
                            trailingIcon = {
                                if (queryState.value.isNotEmpty()) {
                                    IconButton(onClick = { queryState.value = "" }) {
                                        Icon(Icons.Default.Clear, null)
                                    }
                                }
                            }
                        )
                        Button(onClick = {
                            viewMode.value = SearchViewMode.List
                            handleSearch(selectedSort.value.key)
                        }) {
                            Icon(Icons.Default.Search, null)
                        }
                    }
                    Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        FilledTonalButton(onClick = { showFilters.value = true }, modifier = Modifier.weight(1f)) {
                            Icon(Icons.Default.FilterList, null)
                            Spacer(Modifier.width(4.dp))
                            Text("Filtrele")
                        }
                        OutlinedButton(
                            onClick = {
                                viewMode.value = if (viewMode.value == SearchViewMode.Map) SearchViewMode.List else SearchViewMode.Map
                            },
                            modifier = Modifier.weight(1f)
                        ) {
                            Icon(if (viewMode.value == SearchViewMode.Map) Icons.Default.List else Icons.Default.Map, null)
                            Spacer(Modifier.width(4.dp))
                            Text(if (viewMode.value == SearchViewMode.Map) "Liste" else "Harita")
                        }
                    }
                }
            }
        }

        // Sonuç içeriği sadece arama yapıldıysa gösterilir
        if (hasSearched.value) {
            if (viewMode.value == SearchViewMode.Map) {
                val screenHeight = LocalConfiguration.current.screenHeightDp.dp
                Box(modifier = Modifier.fillMaxWidth().height(if (mapExpanded.value) screenHeight else 400.dp)) {
                    Card(modifier = Modifier.fillMaxSize()) {
                        MapSection(
                            places = mapResults.value,
                            onMarkerSelected = { selectedMarkerPlace.value = it },
                            height = if (mapExpanded.value) screenHeight else 400.dp
                        )
                    }

                    // Map Controls Overlay (Zoom Butonlarını Kapatmaması İçin Düzenlendi)
                    Box(modifier = Modifier.align(Alignment.BottomCenter).padding(bottom = 60.dp)) {
                        Surface(
                            color = MaterialTheme.colorScheme.surface.copy(alpha = 0.95f),
                            shape = MaterialTheme.shapes.extraLarge,
                            tonalElevation = 4.dp,
                            shadowElevation = 4.dp
                        ) {
                            Row(
                                modifier = Modifier.padding(horizontal = 16.dp, vertical = 4.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Text("${mapTotal.intValue} Sonuç", style = MaterialTheme.typography.labelLarge)
                                Spacer(Modifier.width(12.dp))
                                IconButton(onClick = { mapPage.intValue = (mapPage.intValue - 1).coerceAtLeast(1) }, enabled = mapPage.intValue > 1) {
                                    Icon(Icons.Default.ChevronLeft, null)
                                }
                                Text("${mapPage.intValue}/${mapTotalPages.intValue}", style = MaterialTheme.typography.bodyMedium)
                                IconButton(onClick = { mapPage.intValue = (mapPage.intValue + 1).coerceAtMost(mapTotalPages.intValue) }, enabled = mapPage.intValue < mapTotalPages.intValue) {
                                    Icon(Icons.Default.ChevronRight, null)
                                }
                            }
                        }
                    }

                    IconButton(
                        onClick = { mapExpanded.value = !mapExpanded.value },
                        modifier = Modifier.align(Alignment.TopEnd).padding(8.dp)
                    ) {
                        Icon(if (mapExpanded.value) Icons.Default.FullscreenExit else Icons.Default.Fullscreen, null)
                    }
                }
            } else {
                // List View
                Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.fillMaxWidth()) {
                    Text("$searchTotal sonuç bulundu", style = MaterialTheme.typography.labelLarge)
                    Spacer(Modifier.weight(1f))
                    Box {
                        TextButton(onClick = { sortExpanded.value = true }) {
                            Icon(Icons.Default.Sort, null)
                            Text(selectedSort.value.label)
                        }
                        DropdownMenu(expanded = sortExpanded.value, onDismissRequest = { sortExpanded.value = false }) {
                            sortOptions.forEach { option ->
                                DropdownMenuItem(text = { Text(option.label) }, onClick = {
                                    selectedSort.value = option
                                    sortExpanded.value = false
                                    handleSearch(option.key)
                                })
                            }
                        }
                    }
                }

                if (searchLoading && searchResults.isEmpty()) {
                    LinearProgressIndicator(modifier = Modifier.fillMaxWidth())
                }

                LazyColumn(state = listState, verticalArrangement = Arrangement.spacedBy(12.dp), modifier = Modifier.fillMaxSize()) {
                    items(searchResults) { place ->
                        PlaceCard(place = place, onClick = { navController.navigate("place/${place.id}") })
                    }
                    if (searchLoading && searchResults.isNotEmpty()) {
                        item {
                            Box(Modifier.fillMaxWidth(), contentAlignment = Alignment.Center) {
                                CircularProgressIndicator(modifier = Modifier.size(24.dp).padding(8.dp))
                            }
                        }
                    }
                }
            }
        } else {
            // Arama yapılmadıysa boş durum görseli veya mesajı eklenebilir
            Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                Text("Mekan bulmak için arama yapın", color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
        }
    }

    // Modal Bottom Sheets (Filtreler)
    if (showFilters.value) {
        ModalBottomSheet(onDismissRequest = { showFilters.value = false }) {
            Column(modifier = Modifier.padding(16.dp).navigationBarsPadding()) {
                Text("Filtrele", style = MaterialTheme.typography.titleLarge)
                Spacer(Modifier.height(16.dp))

                // ŞEHİR BUTONU
                Text("Şehir", style = MaterialTheme.typography.labelMedium)
                OutlinedButton(
                    onClick = { citySheetOpen.value = true },
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp)
                ) {
                    Text(selectedCity.value?.city ?: "Tümü")
                }

                Spacer(Modifier.height(16.dp))

                // KATEGORİ BUTONU
                Text("Kategori", style = MaterialTheme.typography.labelMedium)
                OutlinedButton(
                    onClick = { categorySheetOpen.value = true },
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp)
                ) {
                    Text(selectedCategory.value?.business_type ?: "Tümü")
                }

                Spacer(Modifier.height(24.dp))
                Button(onClick = { showFilters.value = false; handleSearch(selectedSort.value.key) }, modifier = Modifier.fillMaxWidth()) {
                    Text("Sonuçları Göster")
                }
            }
        }
    }

    // Şehir Seçim Modalı
    if (citySheetOpen.value) {
        val filteredCities = cities.filter { it.city.contains(citySearchQuery.value, ignoreCase = true) }
        SearchSelectionSheet(
            title = "Şehirler",
            query = citySearchQuery.value,
            onQueryChange = { citySearchQuery.value = it },
            items = filteredCities,
            onDismiss = { citySheetOpen.value = false },
            onSelect = { selectedCity.value = it; citySheetOpen.value = false },
            onClear = { selectedCity.value = null },
            labelProvider = { it.city }
        )
    }

    // Kategori Seçim Modalı
    if (categorySheetOpen.value) {
        val filteredCats = categories.filter { (it.business_type ?: "").contains(categorySearchQuery.value, ignoreCase = true) }
        SearchSelectionSheet(
            title = "Kategoriler",
            query = categorySearchQuery.value,
            onQueryChange = { categorySearchQuery.value = it },
            items = filteredCats,
            onDismiss = { categorySheetOpen.value = false },
            onSelect = { selectedCategory.value = it; categorySheetOpen.value = false },
            onClear = { selectedCategory.value = null },
            labelProvider = { it.business_type ?: "" },
            countProvider = { "${it.total} işletme" }
        )
    }

    // Marker Detayları
    selectedMarkerPlace.value?.let { place ->
        ModalBottomSheet(onDismissRequest = { selectedMarkerPlace.value = null }) {
            Column(modifier = Modifier.padding(16.dp).navigationBarsPadding(), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                place.business_image?.takeIf { it.isNotBlank() }?.let {
                    Card {
                        Image(
                            painter = rememberAsyncImagePainter(it),
                            contentDescription = null,
                            modifier = Modifier.fillMaxWidth().height(180.dp),
                            contentScale = ContentScale.Crop
                        )
                    }
                }
                Text(place.name, style = MaterialTheme.typography.titleLarge)
                Text(place.formatted_address ?: "", style = MaterialTheme.typography.bodyMedium)

                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    Button(onClick = { navController.navigate("place/${place.id}") }, modifier = Modifier.weight(1f)) {
                        Text("Detay")
                    }
                    Button(onClick = {
                        val gmmIntentUri = Uri.parse("google.navigation:q=${place.latitude},${place.longitude}")
                        val mapIntent = Intent(Intent.ACTION_VIEW, gmmIntentUri).apply { setPackage("com.google.android.apps.maps") }
                        context.startActivity(mapIntent)
                    }, modifier = Modifier.weight(1f)) {
                        Icon(Icons.Default.Directions, null)
                        Text("Yol Tarifi")
                    }
                }
            }
        }
    }
}

private enum class SearchViewMode { Map, List }
private data class MapSearchParams(
    val query: String? = null,
    val category: String? = null,
    val city: String? = null,
    val country: String? = null,
    val sort: String? = null,
    val lat: Double? = null,
    val lng: Double? = null
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun <T> SearchSelectionSheet(
    title: String,
    query: String,
    onQueryChange: (String) -> Unit,
    items: List<T>,
    onDismiss: () -> Unit,
    onSelect: (T) -> Unit,
    onClear: () -> Unit,
    labelProvider: (T) -> String,
    countProvider: ((T) -> String)? = null
) {
    ModalBottomSheet(
        onDismissRequest = onDismiss,
        sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true),
        shape = RoundedCornerShape(topStart = 28.dp, topEnd = 28.dp)
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 20.dp)
                .navigationBarsPadding()
        ) {
            Text(
                text = title,
                style = MaterialTheme.typography.headlineSmall.copy(fontWeight = FontWeight.Bold)
            )
            Spacer(modifier = Modifier.height(16.dp))
            OutlinedTextField(
                value = query,
                onValueChange = onQueryChange,
                placeholder = { Text("$title Ara...") },
                leadingIcon = { Icon(Icons.Default.Search, null) },
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(16.dp),
                keyboardOptions = KeyboardOptions(
                    keyboardType = KeyboardType.Text,
                    imeAction = ImeAction.Search,
                    autoCorrect = true
                )
            )
            Spacer(modifier = Modifier.height(16.dp))
            LazyColumn(
                modifier = Modifier
                    .weight(1f, fill = false)
                    .padding(bottom = 24.dp),
                verticalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                if (query.isEmpty()) {
                    item {
                        Surface(
                            onClick = {
                                onClear()
                                onDismiss()
                            },
                            shape = RoundedCornerShape(12.dp),
                            color = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.4f)
                        ) {
                            Box(modifier = Modifier.fillMaxWidth().padding(16.dp)) {
                                Text(
                                    text = "Tümü",
                                    style = MaterialTheme.typography.bodyLarge,
                                    fontWeight = FontWeight.Bold,
                                    color = MaterialTheme.colorScheme.primary
                                )
                            }
                        }
                    }
                }
                items(items) { item ->
                    Surface(
                        onClick = { onSelect(item) },
                        shape = RoundedCornerShape(12.dp),
                        color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.3f)
                    ) {
                        Column(
                            modifier = Modifier.fillMaxWidth().padding(16.dp),
                            verticalArrangement = Arrangement.spacedBy(4.dp)
                        ) {
                            Text(
                                text = labelProvider(item),
                                style = MaterialTheme.typography.bodyLarge,
                                fontWeight = FontWeight.Medium
                            )
                            countProvider?.let {
                                Text(
                                    text = it(item),
                                    style = MaterialTheme.typography.bodySmall,
                                    color = MaterialTheme.colorScheme.primary,
                                    fontWeight = FontWeight.Bold
                                )
                            }
                        }
                    }
                }
            }
        }
    }
}
