package com.guidexy.app.ui.screens

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
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
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Sort
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.snapshotFlow
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.navigation.NavController
import androidx.core.content.ContextCompat
import android.Manifest
import android.content.pm.PackageManager
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import com.guidexy.app.data.ApiClient
import com.guidexy.app.data.PlaceDto
import com.guidexy.app.ui.PaginationConfig
import com.guidexy.app.ui.PlaceSortOption
import com.guidexy.app.ui.components.LoadingRow
import com.guidexy.app.ui.components.PlaceCard
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.distinctUntilChanged
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import kotlin.math.ceil
import com.google.android.gms.location.LocationServices
import androidx.compose.ui.platform.LocalContext

@Composable
fun CategoryPlacesScreen(
    categorySlug: String,
    categoryName: String?,
    categoryTotal: Int?,
    citySlug: String?,
    cityName: String?,
    navController: NavController,
    onBack: () -> Unit
) {
    val listState = rememberLazyListState()
    val pageState = remember { mutableStateOf(1) }
    val placesState = remember { mutableStateOf<List<PlaceDto>>(emptyList()) }
    val loadingState = remember { mutableStateOf(false) }
    val totalPages = remember { mutableStateOf(1) }
    val totalCount = remember { mutableStateOf(0) }
    val perPage = PaginationConfig.resultsPerPage
    val context = LocalContext.current
    val lastLocation = remember { mutableStateOf<Pair<Double, Double>?>(null) }
    val pendingDistanceSort = remember { mutableStateOf(false) }
    val sortOptions = remember {
        listOf(
            PlaceSortOption("new", "En Yeni"),
            PlaceSortOption("old", "En Eski"),
            PlaceSortOption("name_asc", "Ada Göre (A-Z)"),
            PlaceSortOption("name_desc", "Ada Göre (Z-A)"),
            PlaceSortOption("rating_desc", "En Yüksek Puan"),
            PlaceSortOption("rating_asc", "En Düşük Puan"),
            PlaceSortOption("views", "En Çok Ziyaret Edilen"),
            PlaceSortOption("reviews", "En Çok Yorumlanan"),
            PlaceSortOption("distance", "Uzaklığa Göre")
        )
    }
    val selectedSort = remember { mutableStateOf(sortOptions.first()) }
    val sortExpanded = remember { mutableStateOf(false) }
    val isSortChanging = remember { mutableStateOf(false) }
    val scope = rememberCoroutineScope()

    LaunchedEffect(categorySlug, citySlug) {
        pageState.value = 1
        placesState.value = emptyList()
        totalCount.value = categoryTotal ?: 0
        totalPages.value = if (totalCount.value > 0) {
            ceil(totalCount.value.toDouble() / perPage.toDouble()).toInt().coerceAtLeast(1)
        } else {
            1
        }
    }

    val requestLocationPermission = rememberLauncherForActivityResult(
        ActivityResultContracts.RequestPermission()
    ) { granted ->
        if (granted) pendingDistanceSort.value = true
    }

    val fetchLocationAndReload = {
        val permission = Manifest.permission.ACCESS_FINE_LOCATION
        if (ContextCompat.checkSelfPermission(context, permission) != PackageManager.PERMISSION_GRANTED) {
            requestLocationPermission.launch(permission)
        } else {
            val fusedLocation = LocationServices.getFusedLocationProviderClient(context)
            fusedLocation.lastLocation.addOnSuccessListener { location ->
                if (location != null) {
                    lastLocation.value = location.latitude to location.longitude
                    isSortChanging.value = true
                    pageState.value = 1
                    placesState.value = emptyList()
                }
            }
        }
    }

    LaunchedEffect(pendingDistanceSort.value) {
        if (pendingDistanceSort.value) {
            pendingDistanceSort.value = false
            fetchLocationAndReload()
        }
    }

    LaunchedEffect(categorySlug, citySlug, pageState.value, selectedSort.value.key, lastLocation.value) {
        loadingState.value = true
        val latLng = if (selectedSort.value.key == "distance") lastLocation.value else null
        val response = withContext(Dispatchers.IO) {
            ApiClient.service.placesAlternate(
                mode = "list",
                categorySlug = categorySlug,
                citySlug = citySlug,
                sort = selectedSort.value.key,
                lat = latLng?.first,
                lng = latLng?.second,
                perPage = perPage,
                page = pageState.value
            )
        }
        val resolvedTotal = response.total ?: categoryTotal ?: response.data.size
        totalCount.value = resolvedTotal
        totalPages.value = ceil(resolvedTotal.toDouble() / perPage.toDouble()).toInt().coerceAtLeast(1)
        val newItems = response.data.filter { place ->
            placesState.value.none { it.id == place.id }
        }
        placesState.value = placesState.value + newItems
        loadingState.value = false
        if (pageState.value == 1) {
            isSortChanging.value = false
        }
    }

    Column(modifier = Modifier.fillMaxSize().padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            IconButton(onClick = onBack) {
                Icon(Icons.Default.ArrowBack, contentDescription = "Geri")
            }
            Column {
                Text(cityName ?: categoryName ?: "Kategori", style = MaterialTheme.typography.headlineSmall)
                if (!cityName.isNullOrBlank() && !categoryName.isNullOrBlank()) {
                    Text(categoryName, style = MaterialTheme.typography.titleMedium)
                }
            }
        }
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text(
                "${totalCount.value} sonuç • ${pageState.value}/${totalPages.value}",
                style = MaterialTheme.typography.labelMedium,
                fontWeight = FontWeight.Medium
            )
            Box {
                TextButton(onClick = { sortExpanded.value = true }) {
                    Icon(Icons.Default.Sort, contentDescription = null)
                    Spacer(modifier = Modifier.width(4.dp))
                    Text(selectedSort.value.label)
                }
                DropdownMenu(
                    expanded = sortExpanded.value,
                    onDismissRequest = { sortExpanded.value = false }
                ) {
                    sortOptions.forEach { option ->
                        DropdownMenuItem(
                            text = { Text(option.label) },
                            onClick = {
                                selectedSort.value = option
                                sortExpanded.value = false
                                if (option.key == "distance") {
                                    fetchLocationAndReload()
                                } else {
                                    isSortChanging.value = true
                                    pageState.value = 1
                                    placesState.value = emptyList()
                                    lastLocation.value = null
                                }
                                scope.launch {
                                    if (listState.layoutInfo.totalItemsCount > 0) {
                                        listState.scrollToItem(0)
                                    }
                                }
                            }
                        )
                    }
                }
            }
        }
        if (loadingState.value && placesState.value.isEmpty()) {
            LoadingRow()
        }
        LazyColumn(
            state = listState,
            verticalArrangement = Arrangement.spacedBy(12.dp),
            modifier = Modifier.fillMaxSize()
        ) {
            if (placesState.value.isEmpty() && !loadingState.value) {
                item { Text("Sonuç bulunamadı.") }
            } else {
                items(placesState.value) { place ->
                    PlaceCard(place = place, onClick = { navController.navigate("place/${place.id}") })
                }
                if (loadingState.value && placesState.value.isNotEmpty()) {
                    item { LoadingRow() }
                }
                if (!loadingState.value && pageState.value >= totalPages.value) {
                    item { Text("Tüm sayfalar gösterildi.") }
                }
            }
            item { Spacer(modifier = Modifier.height(24.dp)) }
        }
    }

    LaunchedEffect(listState, placesState.value.size, totalPages.value) {
        snapshotFlow { listState.layoutInfo.visibleItemsInfo.lastOrNull()?.index }
            .distinctUntilChanged()
            .collect { index ->
                if (index != null && index >= placesState.value.size - 2) {
                    if (!loadingState.value && pageState.value < totalPages.value && !isSortChanging.value) {
                        pageState.value += 1
                    }
                }
            }
    }
}
