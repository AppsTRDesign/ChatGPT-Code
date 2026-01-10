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
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.snapshotFlow
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.navigation.NavController
import com.guidexy.app.data.ApiClient
import com.guidexy.app.data.PlaceDto
import com.guidexy.app.ui.PaginationConfig
import com.guidexy.app.ui.components.PlaceCard
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.distinctUntilChanged
import kotlinx.coroutines.withContext
import kotlin.math.ceil

@Composable
fun CategoryPlacesScreen(
    categorySlug: String,
    categoryName: String?,
    categoryTotal: Int?,
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

    LaunchedEffect(categorySlug) {
        pageState.value = 1
        placesState.value = emptyList()
        totalCount.value = categoryTotal ?: 0
        totalPages.value = if (totalCount.value > 0) {
            ceil(totalCount.value.toDouble() / perPage.toDouble()).toInt().coerceAtLeast(1)
        } else {
            1
        }
    }

    LaunchedEffect(categorySlug, pageState.value) {
        loadingState.value = true
        val response = withContext(Dispatchers.IO) {
            ApiClient.service.placesAlternate(
                categorySlug = categorySlug,
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
    }

    Column(modifier = Modifier.fillMaxSize().padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            IconButton(onClick = onBack) {
                Icon(Icons.Default.ArrowBack, contentDescription = "Geri")
            }
            Text(categoryName ?: "Kategori", style = MaterialTheme.typography.headlineSmall)
        }
        Text(
            "${totalCount.value} sonuç • ${pageState.value}/${totalPages.value}",
            style = MaterialTheme.typography.labelMedium,
            fontWeight = FontWeight.Medium
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
            if (placesState.value.isEmpty() && !loadingState.value) {
                item { Text("Sonuç bulunamadı.") }
            } else {
                items(placesState.value) { place ->
                    PlaceCard(place = place, onClick = { navController.navigate("place/${place.id}") })
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
                    if (!loadingState.value && pageState.value < totalPages.value) {
                        pageState.value += 1
                    }
                }
            }
    }
}
