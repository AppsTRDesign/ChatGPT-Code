package com.guidexy.app.ui.screens

import android.Manifest
import android.content.pm.PackageManager
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
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
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.Surface
import androidx.compose.foundation.BorderStroke
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.snapshotFlow
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp
import androidx.core.content.ContextCompat
import androidx.navigation.NavController
import com.guidexy.app.data.ApiClient
import com.guidexy.app.data.PlaceDto
import com.guidexy.app.ui.AppStateViewModel
import com.guidexy.app.ui.PaginationConfig
import com.guidexy.app.ui.PlaceSortOption
import com.guidexy.app.ui.components.LoadingRow
import com.guidexy.app.ui.components.PlaceCard
import com.google.android.gms.location.LocationServices
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.distinctUntilChanged
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import kotlin.math.ceil
import kotlin.math.cos
import kotlin.math.sin
import kotlin.math.sqrt
import kotlin.math.atan2

enum class PlaceListType {
    Popular,
    Latest,
    MostViewed,
    Favorites
}

private data class HighlightResponse(
    val places: List<PlaceDto>,
    val total: Int?
)

@Composable
fun PlaceListScreen(
    listType: PlaceListType,
    viewModel: AppStateViewModel,
    navController: NavController,
    onBack: () -> Unit
) {
    val selectedCountry by viewModel.selectedCountry.collectAsState()
    val listState = rememberLazyListState()
    val pageState = remember { mutableStateOf(1) }
    val placesState = remember { mutableStateOf<List<PlaceDto>>(emptyList()) }
    val loadingState = remember { mutableStateOf(false) }
    val title = when (listType) {
        PlaceListType.Popular -> "Popüler İşletmeler"
        PlaceListType.Latest -> "Son Eklenenler"
        PlaceListType.MostViewed -> "En Çok Görüntülenenler"
        PlaceListType.Favorites -> "Favoriye Eklenenler"
    }
    val perPage = when (listType) {
        PlaceListType.Latest -> PaginationConfig.listLatestPerPage
        PlaceListType.Popular -> PaginationConfig.listPopularPerPage
        PlaceListType.MostViewed -> PaginationConfig.listMostViewedPerPage
        PlaceListType.Favorites -> PaginationConfig.listFavoritesPerPage
    }
    val maxItems = when (listType) {
        PlaceListType.Latest -> PaginationConfig.listLatestMaxItems
        PlaceListType.Popular -> PaginationConfig.listPopularMaxItems
        PlaceListType.MostViewed -> PaginationConfig.listMostViewedMaxItems
        PlaceListType.Favorites -> PaginationConfig.listFavoritesMaxItems
    }
    val totalResults = remember { mutableStateOf(0) }
    val totalPages = remember { mutableStateOf(1) }
    val sortOptions = remember {
        listOf(
            PlaceSortOption("default", "Varsayılan"),
            PlaceSortOption("name_asc", "Ada Göre (A-Z)"),
            PlaceSortOption("name_desc", "Ada Göre (Z-A)"),
            PlaceSortOption("rating_desc", "En Yüksek Puan"),
            PlaceSortOption("views", "En Çok Ziyaret"),
            PlaceSortOption("distance", "Uzaklığa Göre")
        )
    }
    val selectedSort = remember { mutableStateOf(sortOptions.first()) }
    val sortExpanded = remember { mutableStateOf(false) }
    val lastLocation = remember { mutableStateOf<Pair<Double, Double>?>(null) }
    val pendingDistanceSort = remember { mutableStateOf(false) }
    val isSortChanging = remember { mutableStateOf(false) }
    val context = LocalContext.current
    val scope = rememberCoroutineScope()

    val requestLocationPermission = rememberLauncherForActivityResult(
        ActivityResultContracts.RequestPermission()
    ) { granted ->
        if (granted) pendingDistanceSort.value = true
    }

    val fetchLocation = {
        val permission = Manifest.permission.ACCESS_FINE_LOCATION
        if (ContextCompat.checkSelfPermission(context, permission) != PackageManager.PERMISSION_GRANTED) {
            requestLocationPermission.launch(permission)
        } else {
            val fusedLocation = LocationServices.getFusedLocationProviderClient(context)
            fusedLocation.lastLocation.addOnSuccessListener { location ->
                if (location != null) {
                    lastLocation.value = location.latitude to location.longitude
                }
            }
        }
    }

    LaunchedEffect(pendingDistanceSort.value) {
        if (pendingDistanceSort.value) {
            pendingDistanceSort.value = false
            fetchLocation()
        }
    }

    LaunchedEffect(selectedCountry?.code, listType) {
        pageState.value = 1
        placesState.value = emptyList()
        totalResults.value = 0
        totalPages.value = 1
    }

    LaunchedEffect(selectedCountry?.code, pageState.value) {
        val code = selectedCountry?.code ?: return@LaunchedEffect
        if (pageState.value == 1 && placesState.value.isNotEmpty()) return@LaunchedEffect
        loadingState.value = true
        val response = withContext(Dispatchers.IO) {
            when (listType) {
                PlaceListType.Popular -> {
                    val res = ApiClient.service.popular(code, perPage, pageState.value)
                    HighlightResponse(res.places, res.total)
                }
                PlaceListType.Latest -> {
                    val res = ApiClient.service.latest(code, perPage, pageState.value)
                    HighlightResponse(res.places, res.total)
                }
                PlaceListType.MostViewed -> {
                    val res = ApiClient.service.mostViewed(code, perPage, pageState.value)
                    HighlightResponse(res.places, res.total)
                }
                PlaceListType.Favorites -> {
                    val res = ApiClient.service.favorites(code, perPage, pageState.value)
                    HighlightResponse(res.places, res.total)
                }
            }
        }
        val newItems = response.places.filter { place ->
            placesState.value.none { it.id == place.id }
        }
        placesState.value = (placesState.value + newItems).take(maxItems)
        val rawTotal = response.total ?: placesState.value.size
        val total = rawTotal.coerceAtMost(maxItems)
        totalResults.value = total
        totalPages.value = ceil(total / perPage.toDouble()).toInt().coerceAtLeast(1)
        loadingState.value = false
    }

    val sortedPlaces = remember(placesState.value, selectedSort.value, lastLocation.value) {
        val origin = lastLocation.value
        val withDistance = if (selectedSort.value.key == "distance" && origin != null) {
            placesState.value.map { place ->
                val distance = calculateDistanceMeters(origin, place.latitude, place.longitude)
                place.copy(distance_m = if (distance == Double.MAX_VALUE) null else distance)
            }
        } else {
            placesState.value
        }
        when (selectedSort.value.key) {
            "name_asc" -> withDistance.sortedBy { it.name.lowercase() }
            "name_desc" -> withDistance.sortedByDescending { it.name.lowercase() }
            "rating_desc" -> withDistance.sortedByDescending { it.combined_rating ?: it.rating ?: 0.0 }
            "views" -> withDistance.sortedByDescending { it.views ?: 0 }
            "distance" -> {
                if (origin == null) {
                    withDistance
                } else {
                    withDistance.sortedBy { it.distance_m ?: Double.MAX_VALUE }
                }
            }
            else -> withDistance
        }
    }

    Column(modifier = Modifier.fillMaxSize().padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            IconButton(onClick = onBack) {
                Icon(Icons.Default.ArrowBack, contentDescription = "Geri")
            }
            Text(title, style = MaterialTheme.typography.headlineSmall)
        }
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text(
                "${totalResults.value} sonuç • ${pageState.value}/${totalPages.value}",
                style = MaterialTheme.typography.labelMedium
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
                                isSortChanging.value = true
                                scope.launch {
                                    if (listState.layoutInfo.totalItemsCount > 0) {
                                        listState.scrollToItem(0)
                                    }
                                    isSortChanging.value = false
                                }
                                if (option.key == "distance") {
                                    fetchLocation()
                                } else {
                                    lastLocation.value = null
                                }
                            }
                        )
                    }
                }
            }
        }
        if (listType == PlaceListType.MostViewed || listType == PlaceListType.Favorites) {
            Surface(
                modifier = Modifier.fillMaxWidth(),
                shape = MaterialTheme.shapes.medium,
                color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.4f),
                border = BorderStroke(1.dp, MaterialTheme.colorScheme.outline.copy(alpha = 0.4f))
            ) {
                Text(
                    "Bu liste son 1 haftadaki verilere göre güncellenmektedir.",
                    modifier = Modifier.padding(12.dp),
                    style = MaterialTheme.typography.bodySmall
                )
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
            items(sortedPlaces) { place ->
                PlaceCard(place = place, onClick = { navController.navigate("place/${place.id}") })
            }
            if (loadingState.value && placesState.value.isNotEmpty()) {
                item { LoadingRow() }
            }
            if (!loadingState.value && pageState.value >= totalPages.value && totalResults.value > 0) {
                item { Text("Tüm sayfalar gösterildi.") }
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

private fun calculateDistanceMeters(
    origin: Pair<Double, Double>,
    latRaw: String?,
    lngRaw: String?
): Double {
    val lat = latRaw?.toDoubleOrNull()
    val lng = lngRaw?.toDoubleOrNull()
    if (lat == null || lng == null) {
        return Double.MAX_VALUE
    }
    val earthRadius = 6371000.0
    val dLat = Math.toRadians(lat - origin.first)
    val dLng = Math.toRadians(lng - origin.second)
    val a = sin(dLat / 2) * sin(dLat / 2) +
        cos(Math.toRadians(origin.first)) * cos(Math.toRadians(lat)) *
        sin(dLng / 2) * sin(dLng / 2)
    val c = 2 * atan2(sqrt(a), sqrt(1 - a))
    return earthRadius * c
}
