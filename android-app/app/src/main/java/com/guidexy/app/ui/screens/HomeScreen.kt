package com.guidexy.app.ui.screens

import android.Manifest
import android.content.Intent
import android.content.pm.PackageManager
import android.net.Uri
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
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
import androidx.compose.material3.ExposedDropdownMenuBox
import androidx.compose.material3.ExposedDropdownMenuDefaults
import androidx.compose.material3.ExposedDropdownMenu
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.rememberModalBottomSheetState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp
import androidx.core.content.ContextCompat
import androidx.navigation.NavController
import com.google.android.gms.location.LocationServices
import com.guidexy.app.data.CategoryDto
import com.guidexy.app.data.PlaceDto
import com.guidexy.app.ui.AppStateViewModel
import com.guidexy.app.ui.components.PlaceCard
import org.osmdroid.config.Configuration
import org.osmdroid.tileprovider.tilesource.TileSourceFactory
import org.osmdroid.util.GeoPoint
import org.osmdroid.views.MapView
import org.osmdroid.views.overlay.Marker
import java.text.DecimalFormat

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun HomeScreen(viewModel: AppStateViewModel, navController: NavController) {
    val context = LocalContext.current
    val latest by viewModel.latestPlaces.collectAsState()
    val popular by viewModel.popularPlaces.collectAsState()
    val categories by viewModel.categories.collectAsState()
    val cities by viewModel.cities.collectAsState()
    val selectedCountry by viewModel.selectedCountry.collectAsState()
    val selectedCity by viewModel.selectedCity.collectAsState()
    val searchResults by viewModel.searchResults.collectAsState()

    val queryState = remember { mutableStateOf("") }
    val radiusKmState = remember { mutableStateOf("5") }
    val selectedCategory = remember { mutableStateOf<CategoryDto?>(null) }
    val cityExpanded = remember { mutableStateOf(false) }
    val categoryExpanded = remember { mutableStateOf(false) }
    val showAllCategories = remember { mutableStateOf(false) }
    val selectedMarkerPlace = remember { mutableStateOf<PlaceDto?>(null) }

    val distanceFormatter = remember { DecimalFormat("0.0") }
    val nearbyError = remember { mutableStateOf<String?>(null) }

    val requestLocationPermission = rememberLauncherForActivityResult(
        ActivityResultContracts.RequestPermission()
    ) { granted ->
        if (!granted) {
            nearbyError.value = "Konum izni verilmedi."
        }
    }

    LaunchedEffect(selectedCountry?.code) {
        selectedMarkerPlace.value = null
        viewModel.searchPlaces(
            query = queryState.value.takeIf { it.isNotBlank() },
            country = selectedCountry?.code
        )
    }

    LaunchedEffect(selectedCity?.slug) {
        selectedCategory.value = null
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize().padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp)
    ) {
        item {
            Text("Keşfet", style = MaterialTheme.typography.headlineMedium)
        }
        item {
            Card(modifier = Modifier.fillMaxWidth()) {
                Column(
                    modifier = Modifier.padding(16.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    Text("Detaylı Arama", style = MaterialTheme.typography.titleMedium)
                    OutlinedTextField(
                        value = queryState.value,
                        onValueChange = { queryState.value = it },
                        label = { Text("İşletme, kategori veya anahtar kelime") },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true
                    )
                    ExposedDropdownMenuBox(
                        expanded = cityExpanded.value,
                        onExpandedChange = { cityExpanded.value = !cityExpanded.value }
                    ) {
                        OutlinedTextField(
                            value = selectedCity?.city ?: "",
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
                    ExposedDropdownMenuBox(
                        expanded = categoryExpanded.value,
                        onExpandedChange = { categoryExpanded.value = !categoryExpanded.value }
                    ) {
                        OutlinedTextField(
                            value = selectedCategory.value?.business_type ?: "",
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
                    OutlinedTextField(
                        value = radiusKmState.value,
                        onValueChange = { radiusKmState.value = it },
                        label = { Text("Yakınımda km") },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true
                    )
                    Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                        Button(
                            onClick = {
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
                                nearbyError.value = null
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
                                            nearbyError.value = "Konum alınamadı."
                                        } else {
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
                                        nearbyError.value = "Konum alınamadı."
                                    }
                            }
                        ) {
                            Text("Yakınımda")
                        }
                    }
                    nearbyError.value?.let { Text(it, color = MaterialTheme.colorScheme.error) }
                }
            }
        }
        item {
            val mapPlaces = if (searchResults.isNotEmpty()) searchResults else popular
            Card {
                MapSection(
                    places = mapPlaces,
                    onMarkerSelected = { place -> selectedMarkerPlace.value = place }
                )
            }
        }
        if (categories.isNotEmpty()) {
            item {
                Text("Kategoriler", style = MaterialTheme.typography.titleLarge)
                LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    items(categories.take(5)) { category ->
                        CategoryChip(category = category) {
                            selectedCategory.value = category
                            viewModel.searchPlaces(
                                query = queryState.value.takeIf { it.isNotBlank() },
                                category = category.category_slug,
                                city = selectedCity?.slug,
                                country = selectedCountry?.code
                            )
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
        if (searchResults.isNotEmpty()) {
            item { Text("Arama Sonuçları", style = MaterialTheme.typography.titleLarge) }
            items(searchResults) { place ->
                PlaceCard(
                    place = place,
                    onClick = { navController.navigate("place/${place.id}") }
                )
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
                                selectedCategory.value = category
                                showAllCategories.value = false
                                viewModel.searchPlaces(
                                    query = queryState.value.takeIf { it.isNotBlank() },
                                    category = category.category_slug,
                                    city = selectedCity?.slug,
                                    country = selectedCountry?.code
                                )
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
                if (place.distance_m != null) {
                    val km = place.distance_m / 1000.0
                    Text("Uzaklık: ${distanceFormatter.format(km)} km")
                }
                Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                    Button(onClick = { navController.navigate("place/${place.id}") }) {
                        Text("Detay")
                    }
                    Button(onClick = {
                        val lat = place.latitude?.toDoubleOrNull()
                        val lng = place.longitude?.toDoubleOrNull()
                        if (lat != null && lng != null) {
                            val encodedName = Uri.encode(place.name)
                            val uri = Uri.parse("geo:$lat,$lng?q=$lat,$lng($encodedName)")
                            val intent = Intent(Intent.ACTION_VIEW, uri)
                            intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                            context.startActivity(intent)
                        }
                    }) {
                        Text("Yol Tarifi")
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
private fun MapSection(
    places: List<PlaceDto>,
    onMarkerSelected: (PlaceDto) -> Unit
) {
    val context = LocalContext.current
    DisposableEffect(Unit) {
        Configuration.getInstance().userAgentValue = context.packageName
        onDispose { }
    }
    androidx.compose.ui.viewinterop.AndroidView(
        modifier = Modifier.fillMaxWidth().height(260.dp),
        factory = {
            MapView(it).apply {
                setTileSource(TileSourceFactory.MAPNIK)
                setMultiTouchControls(true)
            }
        },
        update = { mapView ->
            mapView.overlays.removeAll { it is Marker }
            val first = places.firstOrNull()
            val center = first?.latitude?.toDoubleOrNull()?.let { lat ->
                val lng = first.longitude?.toDoubleOrNull() ?: return@let null
                GeoPoint(lat, lng)
            } ?: GeoPoint(41.015137, 28.97953)
            mapView.controller.setZoom(12.0)
            mapView.controller.setCenter(center)
            places.forEach { place ->
                val lat = place.latitude?.toDoubleOrNull() ?: return@forEach
                val lng = place.longitude?.toDoubleOrNull() ?: return@forEach
                val marker = Marker(mapView).apply {
                    position = GeoPoint(lat, lng)
                    title = place.name
                    setAnchor(Marker.ANCHOR_CENTER, Marker.ANCHOR_BOTTOM)
                    setOnMarkerClickListener { _, _ ->
                        onMarkerSelected(place)
                        true
                    }
                }
                mapView.overlays.add(marker)
            }
            mapView.invalidate()
        }
    )
}

@Composable
