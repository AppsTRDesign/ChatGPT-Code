package com.guidexy.app.ui.screens

import android.net.Uri
import androidx.compose.foundation.clickable
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
import androidx.compose.material3.Card
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.snapshotFlow
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.navigation.NavController
import com.guidexy.app.data.ApiClient
import com.guidexy.app.data.CategoryDto
import com.guidexy.app.ui.PaginationConfig
import com.guidexy.app.ui.components.LoadingRow
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.distinctUntilChanged
import kotlinx.coroutines.withContext

@Composable
fun CityCategoriesScreen(
    citySlug: String,
    cityName: String?,
    cityTotal: Int?,
    navController: NavController,
    onBack: () -> Unit
) {
    val listState = rememberLazyListState()
    val categoriesState = remember { mutableStateOf<List<CategoryDto>>(emptyList()) }
    val visibleCount = remember { mutableStateOf(PaginationConfig.categoriesPerPage) }
    val loadingState = remember { mutableStateOf(false) }

    LaunchedEffect(citySlug) {
        loadingState.value = true
        val response = withContext(Dispatchers.IO) {
            ApiClient.service.categories(citySlug = citySlug)
        }
        categoriesState.value = response.categories
        visibleCount.value = PaginationConfig.categoriesPerPage
        loadingState.value = false
    }

    val visibleCategories = remember(categoriesState.value, visibleCount.value) {
        categoriesState.value.take(visibleCount.value)
    }

    LaunchedEffect(listState, visibleCategories.size) {
        snapshotFlow { listState.layoutInfo.visibleItemsInfo.lastOrNull()?.index }
            .distinctUntilChanged()
            .collect { index ->
                if (index != null && index >= visibleCategories.size - 2 && visibleCount.value < categoriesState.value.size) {
                    visibleCount.value =
                        (visibleCount.value + PaginationConfig.categoriesPerPage).coerceAtMost(categoriesState.value.size)
                }
            }
    }

    Column(modifier = Modifier.fillMaxSize().padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            IconButton(onClick = onBack) {
                Icon(Icons.Default.ArrowBack, contentDescription = "Geri")
            }
            Column {
                Text(cityName ?: "Şehir", style = MaterialTheme.typography.headlineSmall)
                if (cityTotal != null) {
                    Text("${cityTotal} işletme", style = MaterialTheme.typography.labelMedium)
                }
            }
        }
        if (loadingState.value) {
            LoadingRow()
        }
        LazyColumn(
            state = listState,
            verticalArrangement = Arrangement.spacedBy(12.dp),
            modifier = Modifier.fillMaxSize()
        ) {
            if (categoriesState.value.isEmpty() && !loadingState.value) {
                item { Text("Kategori bulunamadı.") }
            } else {
                items(visibleCategories) { category ->
                    Card(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)) {
                        Column(modifier = Modifier.padding(12.dp)) {
                            Text(category.business_type ?: "Kategori", style = MaterialTheme.typography.titleMedium)
                            Text("Toplam: ${category.total}")
                            Spacer(modifier = Modifier.height(8.dp))
                            Text(
                                text = "İşletmeleri Gör",
                                color = MaterialTheme.colorScheme.primary,
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(vertical = 4.dp)
                                    .clickable {
                                        val slug = category.category_slug ?: return@clickable
                                        val name = category.business_type ?: "Kategori"
                                        val total = category.total
                                        navController.navigate(
                                            "category_places/$slug?name=${Uri.encode(name)}&total=$total&city=$citySlug&cityName=${Uri.encode(cityName ?: "Şehir")}"
                                        )
                                    }
                            )
                        }
                    }
                }
            }
            if (visibleCategories.size < categoriesState.value.size) {
                item { LoadingRow("Daha fazla kategori yükleniyor...") }
            }
        }
    }
}
