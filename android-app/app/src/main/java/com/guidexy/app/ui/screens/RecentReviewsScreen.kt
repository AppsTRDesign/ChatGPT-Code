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
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
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
import com.guidexy.app.data.RecentReviewDto
import com.guidexy.app.ui.AppStateViewModel
import com.guidexy.app.ui.PaginationConfig
import com.guidexy.app.ui.PlaceSortOption
import com.guidexy.app.ui.components.LoadingRow
import com.guidexy.app.ui.components.RecentReviewCard
import com.guidexy.app.ui.components.ReviewGalleryDialog
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.distinctUntilChanged
import kotlinx.coroutines.withContext
import kotlin.math.ceil

@Composable
fun RecentReviewsScreen(
    viewModel: AppStateViewModel,
    navController: NavController,
    onBack: () -> Unit
) {
    val selectedCountry by viewModel.selectedCountry.collectAsState()
    val listState = rememberLazyListState()
    val pageState = remember { mutableStateOf(1) }
    val reviewsState = remember { mutableStateOf<List<RecentReviewDto>>(emptyList()) }
    val loadingState = remember { mutableStateOf(false) }
    val totalCount = remember { mutableStateOf(0) }
    val totalPages = remember { mutableStateOf(1) }
    val isSortChanging = remember { mutableStateOf(false) }
    val sortExpanded = remember { mutableStateOf(false) }
    val galleryState = remember { mutableStateOf<Pair<List<String>, Int>?>(null) }

    val sortOptions = remember {
        listOf(
            PlaceSortOption("new", "En Yeni"),
            PlaceSortOption("old", "En Eski"),
            PlaceSortOption("high", "En Yüksek Puan"),
            PlaceSortOption("low", "En Düşük Puan"),
            PlaceSortOption("likes", "En Beğenilen")
        )
    }
    val selectedSort = remember { mutableStateOf(sortOptions.first()) }

    LaunchedEffect(selectedCountry?.code, selectedSort.value.key) {
        pageState.value = 1
        reviewsState.value = emptyList()
        totalCount.value = 0
        totalPages.value = 1
        isSortChanging.value = true
        listState.scrollToItem(0)
    }

    LaunchedEffect(selectedCountry?.code, pageState.value, selectedSort.value.key) {
        val countryCode = selectedCountry?.code ?: return@LaunchedEffect
        loadingState.value = true
        val response = withContext(Dispatchers.IO) {
            ApiClient.service.recentReviews(
                perPage = PaginationConfig.recentReviewsPerPage,
                page = pageState.value,
                countryCode = countryCode,
                sort = selectedSort.value.key
            )
        }
        val combined = if (pageState.value == 1) {
            response.reviews
        } else {
            reviewsState.value + response.reviews.filter { incoming ->
                reviewsState.value.none { existing ->
                    existing.review_ref == incoming.review_ref && existing.source == incoming.source
                }
            }
        }
        val cappedTotal = response.total.coerceAtMost(PaginationConfig.recentReviewsMaxItems)
        totalCount.value = cappedTotal
        totalPages.value = ceil(cappedTotal / PaginationConfig.recentReviewsPerPage.toDouble())
            .toInt()
            .coerceAtLeast(1)
        reviewsState.value = combined.take(PaginationConfig.recentReviewsMaxItems)
        loadingState.value = false
        if (pageState.value == 1) {
            isSortChanging.value = false
        }
    }

    LaunchedEffect(listState, reviewsState.value.size, totalPages.value) {
        snapshotFlow { listState.layoutInfo.visibleItemsInfo.lastOrNull()?.index }
            .distinctUntilChanged()
            .collect { index ->
                if (index != null && index >= reviewsState.value.size - 2) {
                    if (!loadingState.value && pageState.value < totalPages.value && !isSortChanging.value) {
                        pageState.value += 1
                    }
                }
            }
    }

    Column(
        modifier = Modifier.fillMaxSize().padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            IconButton(onClick = onBack) {
                Icon(Icons.Default.ArrowBack, contentDescription = "Geri")
            }
            Text("Son Yorumlar", style = MaterialTheme.typography.headlineSmall)
        }
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text(
                "${totalCount.value} sonuç • ${pageState.value}/${totalPages.value}",
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
                            }
                        )
                    }
                }
            }
        }
        if (loadingState.value && reviewsState.value.isEmpty()) {
            LoadingRow()
        }
        LazyColumn(
            state = listState,
            verticalArrangement = Arrangement.spacedBy(12.dp),
            modifier = Modifier.fillMaxSize()
        ) {
            if (!loadingState.value && reviewsState.value.isEmpty()) {
                item { Text("Henüz yorum bulunamadı.") }
            } else {
                items(
                    items = reviewsState.value,
                    key = { review -> "${review.source}_${review.review_ref}" }
                ) { review ->
                    RecentReviewCard(
                        review = review,
                        modifier = Modifier.fillMaxWidth(),
                        onClick = { navController.navigate("place/${review.place_id}") },
                        onPhotoClick = { urls, selected ->
                            val index = urls.indexOf(selected).coerceAtLeast(0)
                            galleryState.value = urls to index
                        }
                    )
                }
            }
            if (loadingState.value && reviewsState.value.isNotEmpty()) {
                item { LoadingRow() }
            }
            if (!loadingState.value && pageState.value >= totalPages.value && totalCount.value > 0) {
                item { Text("Tüm sayfalar gösterildi.") }
            }
            item { Spacer(modifier = Modifier.height(24.dp)) }
        }
    }

    galleryState.value?.let { (urls, index) ->
        ReviewGalleryDialog(
            urls = urls,
            initialIndex = index,
            onDismiss = { galleryState.value = null }
        )
    }
}
