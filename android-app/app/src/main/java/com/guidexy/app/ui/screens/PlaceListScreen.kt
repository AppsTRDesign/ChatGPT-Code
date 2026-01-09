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
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
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
import kotlinx.coroutines.delay
import kotlinx.coroutines.withContext
import kotlin.math.ceil

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
    val pageState = remember { mutableStateOf(1) }
    val placesState = remember { mutableStateOf<List<PlaceDto>>(emptyList()) }
    val loadingState = remember { mutableStateOf(false) }
    val title = if (listType == PlaceListType.Popular) "Popüler İşletmeler" else "Son Eklenenler"
    val perPage = PaginationConfig.resultsPerPage
    val totalResults = remember { mutableStateOf(0) }
    val totalPages = remember { mutableStateOf(1) }

    LaunchedEffect(selectedCountry?.code) {
        val code = selectedCountry?.code ?: return@LaunchedEffect
        loadingState.value = true
        val responsePlaces = withContext(Dispatchers.IO) {
            if (listType == PlaceListType.Popular) {
                ApiClient.service.popular(code, PaginationConfig.maxPlaceListResults).places
            } else {
                ApiClient.service.latest(code, PaginationConfig.maxPlaceListResults).places
            }
        }
        val capped = responsePlaces.take(PaginationConfig.maxPlaceListResults)
        placesState.value = capped
        totalResults.value = capped.size
        totalPages.value = ceil(totalResults.value / perPage.toDouble()).toInt().coerceAtLeast(1)
        loadingState.value = false
    }

    LaunchedEffect(pageState.value) {
        if (placesState.value.isNotEmpty()) {
            loadingState.value = true
            delay(200)
            loadingState.value = false
        }
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
        Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
            TextButton(
                onClick = { pageState.value = (pageState.value - 1).coerceAtLeast(1) },
                enabled = pageState.value > 1 && !loadingState.value
            ) {
                Text("Önceki")
            }
            TextButton(
                onClick = { pageState.value = (pageState.value + 1).coerceAtMost(totalPages.value) },
                enabled = pageState.value < totalPages.value && !loadingState.value
            ) {
                Text("Sonraki")
            }
            if (loadingState.value) {
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp), verticalAlignment = Alignment.CenterVertically) {
                    CircularProgressIndicator(modifier = Modifier.height(18.dp).width(18.dp), strokeWidth = 2.dp)
                    Text("Yükleniyor...")
                }
            }
        }
        val startIndex = (pageState.value - 1) * perPage
        val pageItems = placesState.value.drop(startIndex).take(perPage)
        LazyColumn(
            verticalArrangement = Arrangement.spacedBy(12.dp),
            modifier = Modifier.fillMaxSize()
        ) {
            items(pageItems) { place ->
                PlaceCard(place = place, onClick = { navController.navigate("place/${place.id}") })
            }
            if (!loadingState.value && pageState.value >= totalPages.value && totalResults.value > 0) {
                item { Text("Tüm sayfalar gösterildi.") }
            }
            item { Spacer(modifier = Modifier.height(24.dp)) }
        }
    }
}
