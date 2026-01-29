package com.guidexy.app.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.GridItemSpan
import androidx.compose.foundation.lazy.grid.LazyGridScope
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.RemoveRedEye
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.filled.StarBorder
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.rememberModalBottomSheetState
import androidx.compose.material3.surfaceColorAtElevation
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.navigation.NavController
import coil.compose.SubcomposeAsyncImage
import com.guidexy.app.data.CategoryDto
import com.guidexy.app.data.PlaceDto
import com.guidexy.app.data.RecentReviewDto
import com.guidexy.app.ui.AppStateViewModel
import com.guidexy.app.ui.PaginationConfig

// Renk Paleti
private val PrimaryIndigo = Color(0xFF6366F1)
private val StarGold = Color(0xFFFFB800)

@Composable
fun HomeScreen(viewModel: AppStateViewModel, navController: NavController) {
    val latest by viewModel.latestPlaces.collectAsState()
    val popular by viewModel.popularPlaces.collectAsState()
    val mostViewed by viewModel.mostViewedPlaces.collectAsState()
    val favorites by viewModel.favoritePlaces.collectAsState()
    val recentReviews by viewModel.recentReviews.collectAsState()
    val categories by viewModel.categories.collectAsState()

    val showAllCategories = remember { mutableStateOf(false) }
    val categoryQuery = remember { mutableStateOf("") }

    val filteredCategories = remember(categories, categoryQuery.value) {
        val query = categoryQuery.value.trim()
        if (query.isBlank()) categories
        else categories.filter { it.business_type?.contains(query, ignoreCase = true) == true }
    }

    Scaffold(
        topBar = {
            Column(modifier = Modifier.padding(horizontal = 20.dp, vertical = 24.dp)) {
                Text(
                    text = "Keşfet",
                    style = MaterialTheme.typography.displaySmall.copy(
                        fontWeight = FontWeight.Black,
                        letterSpacing = (-1).sp
                    )
                )
                Text(
                    text = "Şehrindeki en iyi noktaları bul",
                    style = MaterialTheme.typography.bodyLarge,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
        }
    ) { innerPadding ->
        LazyVerticalGrid(
            columns = GridCells.Fixed(2),
            modifier = Modifier
                .fillMaxSize()
                .padding(innerPadding),
            contentPadding = PaddingValues(start = 16.dp, end = 16.dp, bottom = 32.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp),
            horizontalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            if (categories.isNotEmpty()) {
                item(span = { GridItemSpan(maxLineSpan) }) {
                    SectionHeader(
                        title = "Kategoriler",
                        actionLabel = "Hepsini Gör",
                        onAction = { showAllCategories.value = true }
                    )
                }
                item(span = { GridItemSpan(maxLineSpan) }) {
                    LazyRow(
                        horizontalArrangement = Arrangement.spacedBy(12.dp),
                        contentPadding = PaddingValues(vertical = 4.dp)
                    ) {
                        items(categories.take(10)) { category ->
                            CategoryChip(category = category) {
                                val name = category.business_type ?: "Kategori"
                                navController.navigate(
                                    "category_places/${category.category_slug}?name=${android.net.Uri.encode(name)}&total=${category.total}"
                                )
                            }
                        }
                    }
                }
            }

            if (recentReviews.isNotEmpty()) {
                item(span = { GridItemSpan(maxLineSpan) }) {
                    Text(
                        text = "Son Yapılan Yorumlar",
                        style = MaterialTheme.typography.titleLarge.copy(fontWeight = FontWeight.Black)
                    )
                }
                item(span = { GridItemSpan(maxLineSpan) }) {
                    LazyRow(
                        horizontalArrangement = Arrangement.spacedBy(12.dp),
                        contentPadding = PaddingValues(vertical = 4.dp)
                    ) {
                        items(recentReviews.take(PaginationConfig.homeRecentReviewsCount)) { review ->
                            RecentReviewCard(review = review) {
                                navController.navigate("place/${review.place_id}")
                            }
                        }
                    }
                }
            }

            renderPlaceGrid("Favoriye Eklenenler", favorites, "favorites_list", navController)
            renderPlaceGrid("Popüler İşletmeler", popular, "popular_list", navController)
            renderPlaceGrid("Son Eklenenler", latest, "latest_list", navController)
            renderPlaceGrid("En Çok Görüntülenenler", mostViewed, "most_viewed_list", navController)

            item(span = { GridItemSpan(maxLineSpan) }) { Spacer(modifier = Modifier.height(24.dp)) }
        }
    }

    if (showAllCategories.value) {
        CategorySearchSheet(
            query = categoryQuery.value,
            onQueryChange = { categoryQuery.value = it },
            list = filteredCategories,
            onDismiss = { showAllCategories.value = false },
            onSelect = { category ->
                showAllCategories.value = false
                val name = category.business_type ?: "Kategori"
                navController.navigate(
                    "category_places/${category.category_slug}?name=${android.net.Uri.encode(name)}&total=${category.total}"
                )
            }
        )
    }
}

private fun LazyGridScope.renderPlaceGrid(
    title: String,
    items: List<PlaceDto>,
    route: String,
    navController: NavController
) {
    if (items.isNotEmpty()) {
        item(span = { GridItemSpan(maxLineSpan) }) {
            SectionHeader(title = title, actionLabel = "Tümünü Gör") {
                navController.navigate(route)
            }
        }
        items(items) { place ->
            ProGridCard(
                place = place,
                onClick = { navController.navigate("place/${place.id}") }
            )
        }
    }
}

@Composable
private fun RecentReviewCard(review: RecentReviewDto, onClick: () -> Unit) {
    Card(
        modifier = Modifier
            .width(260.dp)
            .clickable { onClick() },
        shape = RoundedCornerShape(20.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.35f))
    ) {
        Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            Text(
                review.place_name,
                style = MaterialTheme.typography.titleSmall.copy(fontWeight = FontWeight.Bold)
            )
            review.review_text?.takeIf { it.isNotBlank() }?.let { text ->
                Text(
                    text = text,
                    style = MaterialTheme.typography.bodySmall,
                    maxLines = 3
                )
            }
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(Icons.Default.Star, null, tint = StarGold, modifier = Modifier.size(14.dp))
                Text(
                    "${review.rating} / 5",
                    style = MaterialTheme.typography.labelSmall
                )
            }
            Text(
                review.city_name ?: "",
                style = MaterialTheme.typography.labelSmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
    }
}

@Composable
fun ProGridCard(place: PlaceDto, onClick: () -> Unit) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .clickable { onClick() },
        shape = RoundedCornerShape(20.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.3f))
    ) {
        Column {
            Box(
                modifier = Modifier
                    .height(130.dp)
                    .fillMaxWidth()
            ) {
                SubcomposeAsyncImage(
                    model = place.business_image,
                    contentDescription = place.name,
                    contentScale = ContentScale.Crop,
                    modifier = Modifier.fillMaxSize(),
                    loading = {
                        Box(
                            Modifier
                                .fillMaxSize()
                                .background(Color.LightGray.copy(0.3f))
                        )
                    }
                )
                Surface(
                    modifier = Modifier
                        .padding(8.dp)
                        .align(Alignment.TopEnd),
                    shape = CircleShape,
                    color = Color.Black.copy(alpha = 0.6f)
                ) {
                    Row(
                        modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Icon(Icons.Default.Star, null, tint = StarGold, modifier = Modifier.size(10.dp))
                        Text("${place.combined_rating}", color = Color.White, style = MaterialTheme.typography.labelSmall)
                    }
                }
            }
            Column(modifier = Modifier.padding(12.dp)) {
                Text(
                    text = place.name,
                    maxLines = 1,
                    style = MaterialTheme.typography.titleSmall.copy(fontWeight = FontWeight.Bold, fontSize = 14.sp)
                )

                RatingRow(place.combined_rating)

                Row(verticalAlignment = Alignment.CenterVertically) {
                    Icon(Icons.Default.RemoveRedEye, null, tint = Color.Gray, modifier = Modifier.size(14.dp))
                    Text(
                        text = " Görüntülenme: ${place.views ?: 0}",
                        style = MaterialTheme.typography.labelSmall,
                        color = Color.Gray
                    )
                }
            }
        }
    }
}

@Composable
private fun RatingRow(rating: Double?) {
    val value = rating ?: 0.0
    val filled = value.toInt().coerceIn(0, 5)
    val label = if (rating == null) "-" else String.format("%.1f", value)
    Row(horizontalArrangement = Arrangement.spacedBy(2.dp), verticalAlignment = Alignment.CenterVertically) {
        repeat(5) { index ->
            Icon(
                imageVector = if (index < filled) Icons.Default.Star else Icons.Default.StarBorder,
                contentDescription = null,
                modifier = Modifier.width(14.dp).height(14.dp),
                tint = MaterialTheme.colorScheme.primary
            )
        }
        Text(
            label,
            style = MaterialTheme.typography.labelSmall.copy(fontWeight = FontWeight.Bold),
            color = PrimaryIndigo
        )
    }
}

@Composable
private fun CategoryChip(category: CategoryDto, onClick: () -> Unit) {
    Card(
        modifier = Modifier.clickable { onClick() },
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceColorAtElevation(2.dp))
    ) {
        Column(modifier = Modifier.padding(horizontal = 16.dp, vertical = 10.dp)) {
            Text(
                category.business_type ?: "Kategori",
                style = MaterialTheme.typography.bodyMedium.copy(fontWeight = FontWeight.Bold)
            )
            Text(
                "${category.total} işletme",
                style = MaterialTheme.typography.labelSmall,
                color = PrimaryIndigo
            )
        }
    }
}

@Composable
private fun SectionHeader(title: String, actionLabel: String, onAction: () -> Unit) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(top = 8.dp),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment = Alignment.CenterVertically
    ) {
        Text(title, style = MaterialTheme.typography.titleLarge.copy(fontWeight = FontWeight.Black))
        TextButton(onClick = onAction) {
            Text(
                actionLabel,
                color = PrimaryIndigo,
                style = MaterialTheme.typography.labelLarge.copy(fontWeight = FontWeight.Bold)
            )
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun CategorySearchSheet(
    query: String,
    onQueryChange: (String) -> Unit,
    list: List<CategoryDto>,
    onDismiss: () -> Unit,
    onSelect: (CategoryDto) -> Unit
) {
    ModalBottomSheet(
        onDismissRequest = onDismiss,
        sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true),
        shape = RoundedCornerShape(topStart = 28.dp, topEnd = 28.dp)
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 20.dp)
                .navigationBarsPadding()
        ) {
            Text(
                text = "Kategoriler",
                style = MaterialTheme.typography.headlineSmall.copy(fontWeight = FontWeight.Bold)
            )

            Spacer(modifier = Modifier.height(16.dp))

            OutlinedTextField(
                value = query,
                onValueChange = onQueryChange,
                placeholder = { Text("Kategori Ara...") },
                leadingIcon = { Icon(Icons.Default.Search, null) },
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(16.dp)
            )

            Spacer(modifier = Modifier.height(16.dp))

            androidx.compose.foundation.lazy.LazyColumn(
                modifier = Modifier
                    .weight(1f, fill = false)
                    .padding(bottom = 24.dp),
                verticalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                items(list) { category ->
                    Surface(
                        onClick = { onSelect(category) },
                        shape = RoundedCornerShape(12.dp),
                        color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.3f)
                    ) {
                        Column(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(16.dp),
                            verticalArrangement = Arrangement.spacedBy(4.dp)
                        ) {
                            Text(
                                text = category.business_type ?: "",
                                style = MaterialTheme.typography.bodyLarge,
                                fontWeight = FontWeight.Medium
                            )
                            Text(
                                text = "${category.total} işletme",
                                style = MaterialTheme.typography.bodySmall,
                                color = PrimaryIndigo,
                                fontWeight = FontWeight.Bold
                            )
                        }
                    }
                }
            }
        }
    }
}
