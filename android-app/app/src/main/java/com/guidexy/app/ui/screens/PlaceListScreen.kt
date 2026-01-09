package com.guidexy.app.ui.screens

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
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
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
import com.guidexy.app.ui.components.PlaceCard
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.distinctUntilChanged
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

enum class PlaceListType {
    Popular,
    Latest
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
    val hasMoreState = remember { mutableStateOf(true) }
    val title = if (listType == PlaceListType.Popular) "Popüler İşletmeler" else "Son Eklenenler"
    val perPage = 20
    val totalPages = remember { mutableStateOf<Int?>(null) }

    LaunchedEffect(selectedCountry?.code, pageState.value) {
        val code = selectedCountry?.code ?: return@LaunchedEffect
        loadingState.value = true
        val limit = pageState.value * perPage
        val response = withContext(Dispatchers.IO) {
            if (listType == PlaceListType.Popular) {
                ApiClient.service.popular(code, limit)
            } else {
                ApiClient.service.latest(code, limit)
            }
        }
        val current = placesState.value
        val newItems = response.places.drop(current.size)
        placesState.value = current + newItems
        hasMoreState.value = response.places.size >= limit
        if (!hasMoreState.value) {
            totalPages.value = pageState.value
        }
        loadingState.value = false
    }

    LaunchedEffect(listState, placesState.value.size) {
        snapshotFlow { listState.layoutInfo.visibleItemsInfo.lastOrNull()?.index }
            .distinctUntilChanged()
            .collect { index ->
                if (index != null && index >= placesState.value.size - 2 && hasMoreState.value && !loadingState.value) {
                    pageState.value += 1
                }
            }
    }

    Column(modifier = Modifier.fillMaxSize().padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            IconButton(onClick = onBack) {
                Icon(Icons.Default.ArrowBack, contentDescription = "Geri")
            }
            Text(title, style = MaterialTheme.typography.headlineSmall)
        }
        val totalPagesText = totalPages.value?.let { "/$it" } ?: ""
        Text("Sayfa ${pageState.value}$totalPagesText", style = MaterialTheme.typography.labelMedium)
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
            } else if (!hasMoreState.value) {
                item { Text("Tüm sayfalar yüklendi.") }
            }
            item { Spacer(modifier = Modifier.height(24.dp)) }
        }
    }
}
