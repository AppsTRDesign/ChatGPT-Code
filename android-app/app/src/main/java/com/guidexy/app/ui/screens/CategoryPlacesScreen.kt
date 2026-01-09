package com.guidexy.app.ui.screens

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
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
import com.guidexy.app.ui.components.PlaceCard
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.distinctUntilChanged
import kotlinx.coroutines.withContext
import kotlin.math.ceil

@Composable
fun CategoryPlacesScreen(categorySlug: String, navController: NavController, onBack: () -> Unit) {
    val listState = rememberLazyListState()
    val pageState = remember { mutableStateOf(1) }
    val placesState = remember { mutableStateOf<List<PlaceDto>>(emptyList()) }
    val loadingState = remember { mutableStateOf(false) }
    val totalPages = remember { mutableStateOf(1) }
    val totalCount = remember { mutableStateOf(0) }
    val perPage = 20

    LaunchedEffect(categorySlug, pageState.value) {
        loadingState.value = true
        val response = withContext(Dispatchers.IO) {
            ApiClient.service.placesAlternate(
                categorySlug = categorySlug,
                perPage = perPage,
                page = pageState.value
            )
        }
        totalCount.value = response.total ?: 0
        totalPages.value = if (response.total != null) {
            ceil(response.total.toDouble() / perPage.toDouble()).toInt().coerceAtLeast(1)
        } else {
            pageState.value
        }
        val current = placesState.value
        val newItems = response.data.filter { place -> current.none { it.id == place.id } }
        placesState.value = current + newItems
        loadingState.value = false
    }

    LaunchedEffect(listState, placesState.value.size, totalPages.value) {
        snapshotFlow { listState.layoutInfo.visibleItemsInfo.lastOrNull()?.index }
            .distinctUntilChanged()
            .collect { index ->
                if (index != null && index >= placesState.value.size - 2 && !loadingState.value) {
                    if (pageState.value < totalPages.value) {
                        pageState.value += 1
                    }
                }
            }
    }

    Column(modifier = Modifier.fillMaxSize().padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            IconButton(onClick = onBack) {
                Icon(Icons.Default.ArrowBack, contentDescription = "Geri")
            }
            Text("Kategori İşletmeleri", style = MaterialTheme.typography.headlineSmall)
        }
        val totalText = if (totalCount.value > 0) " • ${totalCount.value} sonuç" else ""
        Text("Sayfa ${pageState.value}/${totalPages.value}$totalText", style = MaterialTheme.typography.labelMedium)
        LazyColumn(
            state = listState,
            verticalArrangement = Arrangement.spacedBy(12.dp),
            modifier = Modifier.fillMaxSize()
        ) {
            items(placesState.value) { place ->
                PlaceCard(place = place, onClick = { navController.navigate("place/${place.id}") })
            }
            if (loadingState.value) {
                item { Text("Yeni sayfa yükleniyor...") }
            } else if (pageState.value >= totalPages.value) {
                item { Text("Tüm sayfalar yüklendi.") }
            }
            item { Spacer(modifier = Modifier.height(24.dp)) }
        }
    }
}
