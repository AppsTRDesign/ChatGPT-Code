package com.guidexy.app.ui.screens

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.snapshotFlow
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.navigation.NavController
import com.guidexy.app.data.ApiClient
import com.guidexy.app.data.PlaceDto
import com.guidexy.app.ui.AppStateViewModel
import com.guidexy.app.ui.PaginationConfig
import com.guidexy.app.ui.components.PlaceCard
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.distinctUntilChanged
import kotlinx.coroutines.withContext
import kotlin.math.ceil

enum class PlaceListType {
    Popular,
    Latest,
    MostViewed
}

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
    }
    val perPage = PaginationConfig.highlightsItemsPerPage
    val maxItems = PaginationConfig.highlightsItems
    val totalResults = remember { mutableStateOf(0) }
    val totalPages = remember { mutableStateOf(1) }

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
                PlaceListType.Popular -> ApiClient.service.popular(code, perPage, pageState.value)
                PlaceListType.Latest -> ApiClient.service.latest(code, perPage, pageState.value)
                PlaceListType.MostViewed -> ApiClient.service.mostViewed(code, perPage, pageState.value)
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

    Column(modifier = Modifier.fillMaxSize().padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            IconButton(onClick = onBack) {
                Icon(Icons.Default.ArrowBack, contentDescription = "Geri")
            }
            Text(title, style = MaterialTheme.typography.headlineSmall)
        }
        Text(
            "${totalResults.value} sonuç • ${pageState.value}/${totalPages.value}",
            style = MaterialTheme.typography.labelMedium
        )
        if (loadingState.value) {
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp), verticalAlignment = Alignment.CenterVertically) {
                CircularProgressIndicator(modifier = Modifier.height(18.dp).width(18.dp), strokeWidth = 2.dp)
                Text("Yükleniyor...")
            }
        }
        LazyColumn(
            state = listState,
            verticalArrangement = Arrangement.spacedBy(12.dp),
            modifier = Modifier.fillMaxSize()
        ) {
            items(placesState.value) { place ->
                PlaceCard(place = place, onClick = { navController.navigate("place/${place.id}") })
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
                    if (!loadingState.value && pageState.value < totalPages.value) {
                        pageState.value += 1
                    }
                }
            }
    }
}
