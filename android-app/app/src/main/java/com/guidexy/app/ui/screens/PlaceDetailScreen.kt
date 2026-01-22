package com.guidexy.app.ui.screens

import android.content.Context
import android.content.Intent
import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.graphics.drawable.BitmapDrawable
import android.graphics.drawable.Drawable
import android.net.Uri
import android.annotation.SuppressLint
import android.os.Build
import android.text.Html
import android.text.method.LinkMovementMethod
import android.util.TypedValue
import android.widget.TextView
import androidx.annotation.RequiresApi
import androidx.core.content.FileProvider
import android.widget.Toast
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.gestures.detectVerticalDragGestures
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.BoxScope
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.fillMaxHeight
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.layout.windowInsetsPadding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.ArrowForward
import androidx.compose.material.icons.filled.Call
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Language
import androidx.compose.material.icons.filled.Link
import androidx.compose.material.icons.filled.Schedule
import androidx.compose.material.icons.filled.Share
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.filled.StarBorder
import androidx.compose.material.icons.filled.Visibility
import androidx.compose.animation.core.animateFloatAsState
import androidx.compose.foundation.ExperimentalFoundationApi
import androidx.compose.foundation.pager.HorizontalPager
import androidx.compose.foundation.pager.rememberPagerState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Divider
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.ScrollableTabRow
import androidx.compose.material3.Tab
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TextField
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableFloatStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.mutableStateListOf
import androidx.compose.runtime.setValue
import androidx.compose.runtime.snapshotFlow
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.graphicsLayer
import androidx.compose.ui.input.pointer.pointerInput
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.draw.alpha
import androidx.compose.ui.draw.blur
import androidx.compose.ui.draw.clip
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.viewinterop.AndroidView
import androidx.compose.ui.window.Dialog
import androidx.compose.ui.window.DialogProperties
import androidx.compose.foundation.layout.statusBars
import androidx.compose.foundation.layout.aspectRatio
import androidx.core.text.HtmlCompat
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import coil.compose.rememberAsyncImagePainter
import com.google.gson.Gson
import com.google.gson.reflect.TypeToken
import com.guidexy.app.data.ApiClient
import com.guidexy.app.data.GoogleReviewDto
import com.guidexy.app.data.ReviewSubmitRequest
import com.guidexy.app.data.ReviewReplyRequest
import com.guidexy.app.data.PlaceDetailDto
import com.guidexy.app.data.PlaceDetailResponse
import com.guidexy.app.data.PlaceHoursDto
import com.guidexy.app.data.PlaceKnowsAboutDto
import com.guidexy.app.data.PlaceSocialDto
import com.guidexy.app.data.PlaceServiceCategoryDto
import com.guidexy.app.data.PlaceServiceDto
import com.guidexy.app.data.PlaceServiceItemDto
import com.guidexy.app.data.PlaceServiceItemPriceDto
import com.guidexy.app.data.UserReviewDto
import com.guidexy.app.data.UserProfileDto
import com.guidexy.app.data.PlaceReviewDto
import com.guidexy.app.data.UserSession
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.toRequestBody
import com.guidexy.app.ui.PaginationConfig
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.distinctUntilChanged
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import java.io.File
import java.io.FileOutputStream
import java.net.URL
import java.text.Normalizer
import java.time.LocalDateTime
import java.time.ZoneId
import java.time.format.DateTimeFormatter
import kotlin.math.ceil
import kotlin.math.abs
import androidx.media3.common.MediaItem
import androidx.media3.exoplayer.ExoPlayer
import androidx.media3.ui.PlayerView

@RequiresApi(Build.VERSION_CODES.O)
@Composable
fun PlaceDetailScreen(placeId: Long, onBack: () -> Unit) {
    val detailState = remember { mutableStateOf<PlaceDetailResponse?>(null) }
    val errorState = remember { mutableStateOf<String?>(null) }
    val loadingState = remember { mutableStateOf(true) }
    val tabState = remember { mutableStateOf(0) }
    val profileState = remember { mutableStateOf<UserProfileDto?>(null) }

    val tabs = listOf(
        "Genel",
        "Fotoğraflar",
        "Yorumlar",
        "Saatler",
        "Yoğunluk",
        "Olanaklar",
        "Hizmetler",
        "Sosyal"
    )

    val context = LocalContext.current

    if (placeId == 0L) {
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(16.dp)
        ) {
            Text("Geçersiz işletme seçimi.", color = MaterialTheme.colorScheme.error)
        }
        return
    }

    LaunchedEffect(placeId) {
        loadingState.value = true
        errorState.value = null
        val response = runCatching {
            withContext(Dispatchers.IO) { ApiClient.service.placeDetail(placeId) }
        }.getOrNull()
        if (response != null) {
            detailState.value = response
        } else {
            val fallback = fetchPlaceDetailFallback(placeId)
            if (fallback != null) {
                detailState.value = fallback
            } else {
                errorState.value = "Detaylar yüklenemedi."
            }
        }
        loadingState.value = false
    }

    LaunchedEffect(Unit) {
        profileState.value = UserSession.currentUser
    }

    if (loadingState.value) {
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(16.dp)
        ) {
            Text("Detaylar yükleniyor...", style = MaterialTheme.typography.titleMedium)
        }
        return
    }

    errorState.value?.let { message ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(16.dp)
        ) {
            Text(message, color = MaterialTheme.colorScheme.error)
        }
        return
    }

    val detail = detailState.value ?: return
    val place = detail.place
    val canReplyToPlace = canEditPlace(place, profileState.value)

    val googleReviews by remember(place.reviews) {
        val parsed = runCatching {
            when (val raw = place.reviews) {
                is List<*> -> {
                    val json = Gson().toJson(raw)
                    Gson().fromJson(
                        json,
                        object : TypeToken<List<GoogleReviewDto>>() {}.type
                    )
                }
                is String -> {
                    if (raw.isBlank()) emptyList()
                    else Gson().fromJson(
                        raw,
                        object : TypeToken<List<GoogleReviewDto>>() {}.type
                    )
                }
                else -> emptyList() // null, map, saçma şey vs
            }
        }.getOrDefault(emptyList())

        mutableStateOf(parsed)
    }

    val reviewSort = remember { mutableStateOf(ReviewSort.NEWEST) }

    val listStates = List(tabs.size) { rememberLazyListState() }
    val listState = listStates[tabState.value]

    val reviewsPerPage = PaginationConfig.reviewsPerPage
    val reviewPage = remember { mutableStateOf(1) }
    val reviewLoading = remember { mutableStateOf(false) }
    val apiReviews = remember { mutableStateListOf<CombinedReview>() }
    val apiTotalPages = remember { mutableStateOf(1) }
    val reviewReplyDialog = remember { mutableStateOf<ReviewReplyTarget?>(null) }
    val reviewReplyText = remember { mutableStateOf("") }

    val combinedReviews = remember(googleReviews, detail.user_reviews) {
        buildCombinedReviews(googleReviews, detail.user_reviews)
    }
    val sortedReviews = remember(combinedReviews, reviewSort.value) {
        sortCombinedReviews(combinedReviews, reviewSort.value)
    }
    val totalReviewPages = remember(sortedReviews.size, reviewsPerPage, apiTotalPages.value, apiReviews.size) {
        if (apiReviews.isNotEmpty()) {
            apiTotalPages.value.coerceAtLeast(1)
        } else {
            ceil(sortedReviews.size / reviewsPerPage.toDouble()).toInt().coerceAtLeast(1)
        }
    }
    val visibleReviews = remember(apiReviews, sortedReviews, reviewPage.value, reviewsPerPage) {
        if (apiReviews.isNotEmpty()) {
            apiReviews.toList()
        } else {
            sortedReviews.take(reviewPage.value * reviewsPerPage)
        }
    }
    val ratingSummary = remember(sortedReviews) { buildRatingSummary(sortedReviews) }

    val reviewRating = remember { mutableStateOf(0) }
    val reviewText = remember { mutableStateOf("") }
    val reviewExtraAnswers = remember { mutableStateOf(mapOf<String, String>()) }
    val showReviewExtraDialog = remember { mutableStateOf(false) }
    val reviewPhotoUris = remember { mutableStateListOf<Uri>() }
    val reviewSubmitState = remember { mutableStateOf<String?>(null) }
    val reviewSubmitting = remember { mutableStateOf(false) }
    val extraFieldKeys = remember(combinedReviews) {
        extractReviewExtraKeys(combinedReviews)
    }

    val userPhotoGroups = remember(combinedReviews) { buildUserPhotoGroups(combinedReviews) }
    val reviewPhotoPage = remember { mutableStateOf(1) }
    val reviewPhotoLoading = remember { mutableStateOf(false) }
    val reviewPhotosPerPage = PaginationConfig.reviewsPerPage
    val totalReviewPhotoPages = remember(userPhotoGroups.size, reviewPhotosPerPage) {
        ceil(userPhotoGroups.size / reviewPhotosPerPage.toDouble()).toInt().coerceAtLeast(1)
    }
    val visibleUserPhotoGroups = remember(userPhotoGroups, reviewPhotoPage.value) {
        userPhotoGroups.take(reviewPhotoPage.value * reviewPhotosPerPage)
    }

    val galleryTabIndex = remember { mutableStateOf(0) }
    val galleryOverlayState = remember { mutableStateOf<GalleryDialogState?>(null) }

    val selectedGalleryId = remember(detail.galleries) {
        mutableStateOf(detail.galleries.firstOrNull()?.id)
    }
    val galleryImages = remember(detail.gallery_images, selectedGalleryId.value) {
        detail.gallery_images.filter { it.gallery_id == selectedGalleryId.value }
    }
    val scope = rememberCoroutineScope()

    // --- Paging & state effects ---

    LaunchedEffect(reviewPage.value, reviewSort.value, tabState.value) {
        if (tabState.value != 2) {
            return@LaunchedEffect
        }
        reviewLoading.value = true
        delay(200)
        val sortParam = when (reviewSort.value) {
            ReviewSort.NEWEST -> "new"
            ReviewSort.OLDEST -> "old"
            ReviewSort.HIGHEST -> "high"
            ReviewSort.LOWEST -> "low"
        }
        val response = runCatching {
            ApiClient.service.placeReviews(placeId, reviewPage.value, sortParam)
        }.getOrNull()
        if (response != null) {
            if (reviewPage.value == 1) {
                apiReviews.clear()
            }
            apiTotalPages.value = response.total_pages
            apiReviews.addAll(response.reviews.map { it.toCombinedReview() })
        }
        reviewLoading.value = false
    }

    LaunchedEffect(tabState.value) {
        if (tabState.value == 2) {
            reviewPage.value = 1
            apiReviews.clear()
            apiTotalPages.value = 1
        }
    }

    LaunchedEffect(sortedReviews.size, reviewsPerPage, apiTotalPages.value, apiReviews.size) {
        val maxPage = if (apiReviews.isNotEmpty()) {
            apiTotalPages.value.coerceAtLeast(1)
        } else {
            ceil(sortedReviews.size / reviewsPerPage.toDouble()).toInt().coerceAtLeast(1)
        }
        if (reviewPage.value > maxPage) {
            reviewPage.value = maxPage
        }
    }

    // Sonsuz scroll – yorumlar
    LaunchedEffect(listState, visibleReviews.size, tabState.value, totalReviewPages) {
        snapshotFlow { listState.layoutInfo.visibleItemsInfo.lastOrNull()?.index }
            .distinctUntilChanged()
            .collect { index ->
                if (tabState.value != 2) return@collect
                if (index != null && index >= visibleReviews.size - 2) {
                    if (!reviewLoading.value && reviewPage.value < totalReviewPages) {
                        reviewPage.value += 1
                    }
                }
            }
    }

    LaunchedEffect(userPhotoGroups.size, reviewPhotosPerPage) {
        val maxPage =
            ceil(userPhotoGroups.size / reviewPhotosPerPage.toDouble()).toInt().coerceAtLeast(1)
        if (reviewPhotoPage.value > maxPage) {
            reviewPhotoPage.value = maxPage
        }
    }

    // Sonsuz scroll – kullanıcı fotoğrafları
    LaunchedEffect(
        listState,
        visibleUserPhotoGroups.size,
        tabState.value,
        galleryTabIndex.value,
        totalReviewPhotoPages
    ) {
        snapshotFlow { listState.layoutInfo.visibleItemsInfo.lastOrNull()?.index }
            .distinctUntilChanged()
            .collect { index ->
                if (tabState.value != 1 || galleryTabIndex.value != 0) return@collect
                if (index != null && index >= visibleUserPhotoGroups.size - 2) {
                    if (!reviewPhotoLoading.value && reviewPhotoPage.value < totalReviewPhotoPages) {
                        reviewPhotoPage.value += 1
                    }
                }
            }
    }

    LaunchedEffect(reviewPhotoPage.value, galleryTabIndex.value, tabState.value) {
        if (tabState.value == 1 && galleryTabIndex.value == 0) {
            reviewPhotoLoading.value = true
            delay(200)
            reviewPhotoLoading.value = false
        }
    }

    // Tab değişince liste başa dönsün / iç durumlar resetlensin
    LaunchedEffect(tabState.value) {
        listState.scrollToItem(0)
        if (tabState.value != 1) {
            galleryTabIndex.value = 0
            selectedGalleryId.value = detail.galleries.firstOrNull()?.id
            reviewPhotoPage.value = 1
            reviewPhotoLoading.value = false
        }
        if (tabState.value != 2) {
            reviewPage.value = 1
        }
    }

    // ---------- UI ----------

    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
    ) {
        Column(modifier = Modifier.fillMaxSize()) {

        // Üst bar
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp, vertical = 8.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            IconButton(onClick = onBack) {
                Icon(Icons.Default.ArrowBack, contentDescription = "Geri")
            }
            Text(
                text = "Geri",
                style = MaterialTheme.typography.bodyMedium
            )
        }

        // Sabit TAB bar (artık sticky hile yok, direkt üstte duruyor)
        ScrollableTabRow(
            selectedTabIndex = tabState.value,
            edgePadding = 12.dp,
            modifier = Modifier.fillMaxWidth()
        ) {
            tabs.forEachIndexed { index, label ->
                Tab(
                    selected = tabState.value == index,
                    onClick = { tabState.value = index },
                    text = { Text(label) }
                )
            }
        }

        Divider(modifier = Modifier.fillMaxWidth())

        // Sayfanın geri kalanı: tek LazyColumn, tüm içerik aynı scroll’da
        LazyColumn(
            state = listState,
            modifier = Modifier
                .fillMaxSize()
                .padding(horizontal = 16.dp, vertical = 12.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {

            // ---- Üst İşletme Kartı ----
            item {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
                    colors = CardDefaults.cardColors(
                        containerColor = MaterialTheme.colorScheme.surface
                    )
                ) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                            Card(
                                modifier = Modifier
                                    .width(96.dp)
                                    .height(96.dp),
                                shape = MaterialTheme.shapes.medium,
                                elevation = CardDefaults.cardElevation(0.dp)
                            ) {
                                Image(
                                    painter = rememberAsyncImagePainter(place.business_image),
                                    contentDescription = place.name,
                                    modifier = Modifier.fillMaxSize(),
                                    contentScale = ContentScale.Crop
                                )
                            }

                            Column(
                                verticalArrangement = Arrangement.spacedBy(4.dp),
                                modifier = Modifier.weight(1f)
                            ) {
                                Text(
                                    place.name,
                                    style = MaterialTheme.typography.titleLarge,
                                    maxLines = 2,
                                    overflow = TextOverflow.Ellipsis
                                )

                                val ratingText = place.rating ?: place.combined_rating
                                if (ratingText != null) {
                                    Row(verticalAlignment = Alignment.CenterVertically) {
                                        StarRating(rating = ratingText)
                                        Text(
                                            " (${place.user_ratings_total ?: place.total_reviews ?: 0})",
                                            style = MaterialTheme.typography.labelSmall
                                        )
                                    }
                                }

                                Row(
                                    horizontalArrangement = Arrangement.spacedBy(6.dp),
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Icon(
                                        Icons.Default.Visibility,
                                        contentDescription = null,
                                        modifier = Modifier.size(16.dp)
                                    )
                                    Text(
                                        "${place.views ?: 0} görüntülenme",
                                        style = MaterialTheme.typography.bodySmall
                                    )
                                }

                                Text(
                                    place.formatted_address ?: "",
                                    style = MaterialTheme.typography.bodySmall,
                                    maxLines = 2,
                                    overflow = TextOverflow.Ellipsis
                                )

                                place.business_type?.let {
                                    Text(
                                        it,
                                        style = MaterialTheme.typography.bodySmall,
                                        maxLines = 1,
                                        overflow = TextOverflow.Ellipsis
                                    )
                                }

                                place.current_status?.let { status ->
                                    val isOpen = status.status == "open"
                                    val statusText =
                                        formatStatusText(status.text ?: "-") ?: "-"
                                    StatusBadge(status = PlaceStatus(statusText, isOpen))
                                }
                            }
                        }

                        Spacer(modifier = Modifier.height(12.dp))

                        val lat = place.latitude?.toDoubleOrNull()
                        val lng = place.longitude?.toDoubleOrNull()
                        if (lat != null && lng != null) {
                            LazyRow(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.spacedBy(12.dp)
                            ) {

                                // Yol tarifi
                                item {
                                    Button(
                                        onClick = {
                                            val encodedName = Uri.encode(place.name)
                                            val uri =
                                                Uri.parse("geo:$lat,$lng?q=$lat,$lng($encodedName)")
                                            val intent = Intent(Intent.ACTION_VIEW, uri)
                                            intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                                            context.startActivity(intent)
                                        }
                                    ) {
                                        Row(
                                            horizontalArrangement = Arrangement.spacedBy(6.dp),
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Icon(
                                                Icons.Default.ArrowForward,
                                                contentDescription = null
                                            )
                                            Text("Yol Tarifi")
                                        }
                                    }
                                }

                                // Ara
                                if (!place.formatted_phone_number.isNullOrBlank()) {
                                    item {
                                        Button(
                                            onClick = {
                                                val intent = Intent(
                                                    Intent.ACTION_DIAL,
                                                    Uri.parse("tel:${place.formatted_phone_number}")
                                                )
                                                intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                                                context.startActivity(intent)
                                            }
                                        ) {
                                            Row(
                                                horizontalArrangement = Arrangement.spacedBy(6.dp),
                                                verticalAlignment = Alignment.CenterVertically
                                            ) {
                                                Icon(
                                                    Icons.Default.Call,
                                                    contentDescription = null
                                                )
                                                Text("Ara")
                                            }
                                        }
                                    }
                                }

                                // Web
                                if (!place.website.isNullOrBlank()) {
                                    item {
                                        Button(
                                            onClick = {
                                                val intent = Intent(
                                                    Intent.ACTION_VIEW,
                                                    Uri.parse(place.website)
                                                )
                                                intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                                                context.startActivity(intent)
                                            }
                                        ) {
                                            Row(
                                                horizontalArrangement = Arrangement.spacedBy(6.dp),
                                                verticalAlignment = Alignment.CenterVertically
                                            ) {
                                                Icon(
                                                    Icons.Default.Language,
                                                    contentDescription = null
                                                )
                                                Text("Web")
                                            }
                                        }
                                    }
                                }

                                // Paylaş
                                item {
                                    Button(
                                        onClick = {
                                            val imageUrl = place.business_image
                                            val ctx = context

                                            scope.launch(Dispatchers.IO) {
                                                try {
                                                    val url = URL(imageUrl)
                                                    val bitmap =
                                                        BitmapFactory.decodeStream(url.openStream())

                                                    val file = File(
                                                        ctx.cacheDir,
                                                        "share_${place.id}.png"
                                                    )
                                                    FileOutputStream(file).use { out ->
                                                        bitmap.compress(
                                                            Bitmap.CompressFormat.PNG,
                                                            100,
                                                            out
                                                        )
                                                    }

                                                    val uri = FileProvider.getUriForFile(
                                                        ctx,
                                                        "${ctx.packageName}.provider",
                                                        file
                                                    )

                                                    val slug = permalink(place.name)
                                                    val shareUrl =
                                                        "https://guidexy.com/${place.id}-$slug"
                                                    val text = "${place.name}\n$shareUrl"

                                                    withContext(Dispatchers.Main) {
                                                        val shareIntent =
                                                            Intent(Intent.ACTION_SEND).apply {
                                                                type = "image/*"
                                                                putExtra(
                                                                    Intent.EXTRA_TEXT,
                                                                    text
                                                                )
                                                                putExtra(
                                                                    Intent.EXTRA_STREAM,
                                                                    uri
                                                                )
                                                                addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
                                                            }
                                                        val chooser = Intent.createChooser(
                                                            shareIntent,
                                                            "Paylaş"
                                                        )
                                                        chooser.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                                                        ctx.startActivity(chooser)
                                                    }
                                                } catch (e: Exception) {
                                                    e.printStackTrace()
                                                }
                                            }
                                        }
                                    ) {
                                        Row(
                                            horizontalArrangement = Arrangement.spacedBy(6.dp),
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Icon(
                                                Icons.Default.Share,
                                                contentDescription = "Paylaş"
                                            )
                                            Text("Paylaş")
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // ---- Üst mini galeri (slider havası) ----
            if (detail.gallery_images.isNotEmpty()) {
                item {
                    LazyRow(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        items(detail.gallery_images.take(5)) { image ->
                            Card(
                                modifier = Modifier
                                    .width(160.dp)
                                    .height(110.dp),
                                shape = MaterialTheme.shapes.medium,
                                elevation = CardDefaults.cardElevation(1.dp)
                            ) {
                                Image(
                                    painter = rememberAsyncImagePainter(image.image_url),
                                    contentDescription = place.name,
                                    contentScale = ContentScale.Crop,
                                    modifier = Modifier.fillMaxSize()
                                )
                            }
                        }
                    }
                }
            }

            // Yorum tabında başlık + sayfa bilgisi
            if (tabState.value == 2) {
                item {
                    Column(
                        modifier = Modifier.fillMaxWidth(),
                        verticalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        Text(
                            "${sortedReviews.size} yorum • ${reviewPage.value}/$totalReviewPages",
                            style = MaterialTheme.typography.labelMedium
                        )
                        if (reviewLoading.value) {
                            Row(
                                horizontalArrangement = Arrangement.spacedBy(8.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                CircularProgressIndicator(
                                    modifier = Modifier
                                        .height(18.dp)
                                        .width(18.dp),
                                    strokeWidth = 2.dp
                                )
                                Text("Yükleniyor...")
                            }
                        }
                    }
                }
            }

            // -------- TAB İÇERİKLERİ --------
            when (tabState.value) {

                // GENEL
                0 -> {
                    item {
                        HtmlText(place.description ?: "Açıklama yok")
                    }
                }

                // FOTOĞRAFLAR
                1 -> {
                    // Üst fotoğraf sekmeleri (kullanıcı / galeriler)
                    item {
                        val galleryTabs =
                            listOf("Kullanıcı Fotoğrafları") + detail.galleries.map { it.title }
                        ScrollableTabRow(
                            selectedTabIndex = galleryTabIndex.value,
                            edgePadding = 12.dp
                        ) {
                            galleryTabs.forEachIndexed { index, title ->
                                Tab(
                                    selected = galleryTabIndex.value == index,
                                    onClick = {
                                        galleryTabIndex.value = index
                                        if (index > 0) {
                                            selectedGalleryId.value =
                                                detail.galleries[index - 1].id
                                        }
                                    },
                                    text = { Text(title) }
                                )
                            }
                        }
                        Spacer(modifier = Modifier.height(12.dp))
                    }

                    if (galleryTabIndex.value == 0) {
                        // Kullanıcı foto grupları
                        if (userPhotoGroups.isEmpty()) {
                            item { Text("Kullanıcı fotoğrafı bulunamadı.") }
                        } else {
                            items(visibleUserPhotoGroups) { group ->
                                Card(
                                    modifier = Modifier.fillMaxWidth(),
                                    elevation = CardDefaults.cardElevation(1.dp)
                                ) {
                                    Column(
                                        modifier = Modifier.padding(12.dp),
                                        verticalArrangement = Arrangement.spacedBy(12.dp)
                                    ) {
                                        Row(
                                            horizontalArrangement = Arrangement.spacedBy(8.dp),
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            if (!group.profilePhotoUrl.isNullOrBlank()) {
                                                Card(
                                                    modifier = Modifier
                                                        .width(36.dp)
                                                        .height(36.dp),
                                                    shape = MaterialTheme.shapes.medium
                                                ) {
                                                    Image(
                                                        painter = rememberAsyncImagePainter(group.profilePhotoUrl),
                                                        contentDescription = group.authorName,
                                                        contentScale = ContentScale.Crop,
                                                        modifier = Modifier.fillMaxSize()
                                                    )
                                                }
                                            } else {
                                                Card(
                                                    modifier = Modifier
                                                        .width(36.dp)
                                                        .height(36.dp),
                                                    shape = MaterialTheme.shapes.medium
                                                ) {
                                                    Box(
                                                        modifier = Modifier.fillMaxSize(),
                                                        contentAlignment = Alignment.Center
                                                    ) {
                                                        Text(
                                                            text = group.authorName?.take(1)
                                                                ?.uppercase() ?: "?"
                                                        )
                                                    }
                                                }
                                            }
                                            Column {
                                                Text(
                                                    group.authorName ?: "Kullanıcı",
                                                    style = MaterialTheme.typography.labelMedium
                                                )
                                                group.createdAt?.let { createdAt ->
                                                    Text(
                                                        formatReviewDate(createdAt) ?: createdAt,
                                                        style = MaterialTheme.typography.labelSmall
                                                    )
                                                }
                                            }
                                        }

                                        LazyRow(
                                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                                        ) {
                                            items(group.photos) { url ->
                                                Card(
                                                    modifier = Modifier
                                                        .width(140.dp)
                                                        .height(100.dp)
                                                        .clickable {
                                                            val items = group.photos.map { item ->
                                                                GalleryMedia(
                                                                    url = item,
                                                                    isVideo = isVideoUrl(item)
                                                                )
                                                            }
                                                            val selectedIndex =
                                                                group.photos.indexOf(url)
                                                                    .coerceAtLeast(0)
                                                            galleryOverlayState.value =
                                                                GalleryDialogState(
                                                                    items = items,
                                                                    initialIndex = selectedIndex
                                                                )
                                                        },
                                                    shape = MaterialTheme.shapes.medium
                                                ) {
                                                    Image(
                                                        painter = rememberAsyncImagePainter(url),
                                                        contentDescription = null,
                                                        contentScale = ContentScale.Crop,
                                                        modifier = Modifier.fillMaxSize()
                                                    )
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            item {
                                Text(
                                    "${userPhotoGroups.size} kullanıcı • ${reviewPhotoPage.value}/$totalReviewPhotoPages",
                                    style = MaterialTheme.typography.labelMedium
                                )
                            }

                            if (reviewPhotoLoading.value) {
                                item {
                                    Row(
                                        horizontalArrangement = Arrangement.spacedBy(8.dp),
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        CircularProgressIndicator(
                                            modifier = Modifier
                                                .height(18.dp)
                                                .width(18.dp),
                                            strokeWidth = 2.dp
                                        )
                                        Text("Yükleniyor...")
                                    }
                                }
                            }

                            if (!reviewPhotoLoading.value && reviewPhotoPage.value >= totalReviewPhotoPages) {
                                item { Text("Tüm fotoğraflar gösterildi.") }
                            }
                        }
                    } else {
                        // Klasik galeriler – BURAYI artık ana scroll’a bağladık (grid yok, satır satır)
                        item {
                            Text(
                                "Galeriler",
                                style = MaterialTheme.typography.titleMedium
                            )
                            if (galleryImages.isEmpty()) {
                                Text("Bu galeri için görsel bulunamadı.")
                            }
                        }

                        if (galleryImages.isNotEmpty()) {
                            // 2'şer sütun halinde tek LazyColumn içinde akacak
                            val rows = galleryImages.chunked(2)
                            items(rows) { rowImages ->
                                Row(
                                    modifier = Modifier.fillMaxWidth(),
                                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                                ) {
                                    rowImages.forEach { image ->
                                        Card(
                                            modifier = Modifier
                                                .weight(1f)
                                                .clickable {
                                                    val items = galleryImages.map { galleryImage ->
                                                        GalleryMedia(
                                                            url = galleryImage.image_url,
                                                            isVideo = isVideoUrl(galleryImage.image_url)
                                                        )
                                                    }
                                                    val selectedIndex =
                                                        galleryImages.indexOf(image).coerceAtLeast(0)
                                                    galleryOverlayState.value = GalleryDialogState(
                                                        items = items,
                                                        initialIndex = selectedIndex
                                                    )
                                                },
                                            shape = MaterialTheme.shapes.medium,
                                            elevation = CardDefaults.cardElevation(1.dp)
                                        ) {
                                            Image(
                                                painter = rememberAsyncImagePainter(
                                                    image.thumb_url.ifBlank {
                                                        image.image_url
                                                    }
                                                ),
                                                contentDescription = null,
                                                contentScale = ContentScale.Crop,
                                                modifier = Modifier
                                                    .fillMaxWidth()
                                                    .height(140.dp)
                                            )
                                        }
                                    }

                                    // Tek eleman kaldıysa satırı dengelemek için boş Box
                                    if (rowImages.size == 1) {
                                        Spacer(modifier = Modifier.weight(1f))
                                    }
                                }
                            }
                        }
                    }
                }

                // YORUMLAR
                2 -> {
                    item {
                        if (profileState.value == null) {
                            Card(
                                modifier = Modifier.fillMaxWidth(),
                                border = BorderStroke(
                                    1.dp,
                                    MaterialTheme.colorScheme.primary
                                ),
                                colors = CardDefaults.cardColors(
                                    containerColor = MaterialTheme.colorScheme.surfaceVariant
                                )
                            ) {
                                Column(modifier = Modifier.padding(16.dp)) {
                                    Text(
                                        "Yorum yapmak için giriş yapın.",
                                        style = MaterialTheme.typography.titleSmall,
                                        color = MaterialTheme.colorScheme.primary
                                    )
                                    Text(
                                        "Giriş yaptıktan sonra deneyiminizi paylaşabilirsiniz.",
                                        style = MaterialTheme.typography.bodySmall
                                    )
                                }
                            }
                        } else {
                            ReviewComposerCard(
                                rating = reviewRating.value,
                                onRatingChange = { reviewRating.value = it },
                                text = reviewText.value,
                                onTextChange = { reviewText.value = it },
                                extraKeys = extraFieldKeys,
                                extraAnswers = reviewExtraAnswers.value,
                                onOpenExtras = { showReviewExtraDialog.value = true },
                                photoUris = reviewPhotoUris,
                                onRemovePhoto = { uri -> reviewPhotoUris.remove(uri) },
                                onAddPhotos = { uris ->
                                    val remaining = (10 - reviewPhotoUris.size).coerceAtLeast(0)
                                    reviewPhotoUris.addAll(uris.take(remaining))
                                },
                                onSubmit = {
                                    if (reviewSubmitting.value) return@ReviewComposerCard
                                    val currentUser = profileState.value
                                    if (currentUser == null) {
                                        reviewSubmitState.value = "Yorum yapmak için giriş yapın."
                                        return@ReviewComposerCard
                                    }
                                    reviewSubmitting.value = true
                                    reviewSubmitState.value = null
                                    scope.launch {
                                        val uploadedUrls = uploadReviewPhotos(
                                            context = context,
                                            placeId = placeId,
                                            userId = currentUser.id,
                                            uris = reviewPhotoUris.toList()
                                        )
                                        val request = ReviewSubmitRequest(
                                            place_id = placeId,
                                            user_id = currentUser.id,
                                            user_name = currentUser.name,
                                            user_email = currentUser.email,
                                            user_role = currentUser.role,
                                            rating = reviewRating.value,
                                            text = reviewText.value,
                                            text_extra = reviewExtraAnswers.value,
                                            review_photo_urls = uploadedUrls
                                        )
                                    val response = runCatching {
                                        ApiClient.service.submitReview(request)
                                    }.getOrNull()
                                    if (response?.error != null) {
                                        val errorMessage = when (response.error) {
                                            "review_cooldown" -> "Bu işletmeye 2 hafta içinde tekrar yorum yapabilirsiniz."
                                            else -> "Yorum gönderilemedi: ${response.error}"
                                        }
                                        reviewSubmitState.value = errorMessage
                                        Toast
                                            .makeText(
                                                context,
                                                errorMessage,
                                                Toast.LENGTH_SHORT
                                            )
                                            .show()
                                    } else if (response?.status == "review_pending" || response?.success == true) {
                                        reviewSubmitState.value = "Yorumunuz gönderildi, onay bekliyor."
                                        Toast
                                            .makeText(
                                                context,
                                                "Yorumunuz gönderildi.",
                                                Toast.LENGTH_SHORT
                                            )
                                            .show()
                                        reviewRating.value = 0
                                        reviewText.value = ""
                                        reviewExtraAnswers.value = emptyMap()
                                            reviewPhotoUris.clear()
                                    } else {
                                        reviewSubmitState.value = "Yorum gönderilemedi."
                                        Toast
                                            .makeText(
                                                context,
                                                "Yorum gönderilemedi.",
                                                Toast.LENGTH_SHORT
                                            )
                                            .show()
                                    }
                                    reviewSubmitting.value = false
                                }
                            },
                                submitMessage = reviewSubmitState.value,
                                isSubmitting = reviewSubmitting.value
                            )
                        }
                    }
                    item {
                        ReviewSummaryCard(summary = ratingSummary)
                    }
                    item {
                        ReviewSortRow(
                            selected = reviewSort.value,
                            onSelect = {
                                reviewSort.value = it
                                reviewPage.value = 1
                                apiReviews.clear()
                                apiTotalPages.value = 1
                            }
                        )
                    }
                    if (sortedReviews.isEmpty()) {
                        item { Text("Henüz yorum yok.") }
                    } else {
                        items(visibleReviews) { review ->
                            ReviewCard(
                                review = review,
                                onOpenGallery = { urls, selectedUrl ->
                                    val items = urls.map { item ->
                                        GalleryMedia(
                                            url = item,
                                            isVideo = isVideoUrl(item)
                                        )
                                    }
                                    val selectedIndex = urls.indexOf(selectedUrl).coerceAtLeast(0)
                                    galleryOverlayState.value = GalleryDialogState(
                                        items = items,
                                        initialIndex = selectedIndex
                                    )
                                },
                                canReply = canReplyToPlace,
                                onReply = { target ->
                                    reviewReplyText.value = target.replyText.orEmpty()
                                    reviewReplyDialog.value = ReviewReplyTarget(
                                        review = target
                                    )
                                }
                            )
                        }
                        if (!reviewLoading.value && reviewPage.value >= totalReviewPages) {
                            item { Text("Tüm yorumlar gösterildi.") }
                        }
                    }
                }

                // SAATLER
                3 -> {
                    item { SaatlerTab(hours = detail.hours,openingHours = detail.place.opening_hours) }
                }

                // YOĞUNLUK
                4 -> {
                    val busyHours = parseBusyHours(place.busy_hours?.toString())
                    if (busyHours.isEmpty()) {
                        item { Text("Yoğunluk verisi bulunamadı.") }
                    } else {
                        item { BusyHoursChart(busyHours = busyHours) }
                    }
                }

                // OLANAKLAR
                5 -> {
                    val grouped = groupServices(detail.knows_about)
                    if (grouped.isEmpty()) {
                        item { Text("Olanak bulunamadı.") }
                    } else {
                        grouped.forEach { group ->
                            item { ServiceGroupCard(group = group) }
                        }
                    }
                }

                // HİZMETLER
                6 -> {
                    val serviceSections = buildServiceSections(
                        services = detail.services,
                        categories = detail.service_categories,
                        items = detail.service_items,
                        prices = detail.service_item_prices
                    )
                    if (serviceSections.isEmpty()) {
                        item { Text("Hizmet bulunamadı.") }
                    } else {
                        serviceSections.forEach { section ->
                            item { ServiceSectionCard(section) }
                        }
                    }
                }

                // SOSYAL
                7 -> {
                    if (detail.social_links.isEmpty()) {
                        item { Text("Sosyal bağlantı yok.") }
                    } else {
                        items(detail.social_links) { link ->
                            SocialLinkCard(link = link)
                        }
                    }
                }
            }
        }
        }

        // --- Dialoglar ---
        if (showReviewExtraDialog.value) {
            ReviewExtraDialog(
                keys = extraFieldKeys,
                values = reviewExtraAnswers.value,
                onDismiss = { showReviewExtraDialog.value = false },
                onSave = { updated ->
                    reviewExtraAnswers.value = updated
                    showReviewExtraDialog.value = false
                }
            )
        }
        reviewReplyDialog.value?.let { target ->
            ReviewReplyDialog(
                review = target.review,
                replyText = reviewReplyText.value,
                onReplyChange = { reviewReplyText.value = it },
                onDismiss = { reviewReplyDialog.value = null },
                onSubmit = {
                    if (!canReplyToPlace) return@ReviewReplyDialog
                    val currentUser = profileState.value ?: return@ReviewReplyDialog
                    scope.launch {
                        val response = runCatching {
                            ApiClient.service.submitReviewReply(
                                ReviewReplyRequest(
                                    place_id = place.id,
                                    source = target.review.source,
                                    review_ref = target.review.reviewRef,
                                    reply_text = reviewReplyText.value,
                                    user_id = currentUser.id,
                                    user_role = currentUser.role
                                )
                            )
                        }.getOrNull()
                        if (response?.error == null) {
                            reviewReplyDialog.value = null
                        }
                    }
                }
            )
        }
        galleryOverlayState.value?.let { state ->
            GalleryDialog(state = state, onDismiss = { galleryOverlayState.value = null })
        }
    }
}

@SuppressLint("NewApi")
@Composable
private fun ReviewCard(
    review: CombinedReview,
    onOpenGallery: (List<String>, String) -> Unit,
    canReply: Boolean,
    onReply: (CombinedReview) -> Unit
) {
    val expanded = remember { mutableStateOf(false) }
    val hasExtras = review.textExtra.isNotEmpty()

    Card(
        modifier = Modifier.fillMaxWidth(),
        elevation = CardDefaults.cardElevation(1.dp)
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                ReviewAvatar(review)
                Column {
                    Text(
                        review.authorName ?: "Yorumcu",
                        style = MaterialTheme.typography.titleMedium
                    )
                    review.rating?.let { StarRating(rating = it.toDouble()) }
                    review.createdAt?.let { createdAt ->
                        Text(
                            formatReviewDate(createdAt) ?: createdAt,
                            style = MaterialTheme.typography.labelSmall
                        )
                    }
                }
            }

            review.text?.let { text ->
                Text(
                    text = text,
                    maxLines = if (expanded.value) Int.MAX_VALUE else 3,
                    overflow = TextOverflow.Ellipsis
                )
            }
            val showToggle = (review.text?.length ?: 0) > 120 || hasExtras
            if (showToggle) {
                TextButton(onClick = { expanded.value = !expanded.value }) {
                    Text(if (expanded.value) "Kısalt" else "Tümünü Gör")
                }
            }

            if (hasExtras && expanded.value) {
                Spacer(modifier = Modifier.height(8.dp))
                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    review.textExtra.forEach { (key, value) ->
                        ReviewExtraCard(label = key, value = value)
                    }
                }
            }

            if (review.photoUrls.isNotEmpty()) {
                Spacer(modifier = Modifier.height(8.dp))
                LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    items(review.photoUrls) { url ->
                        val isLead = url == review.photoUrls.first()
                        Card(
                            modifier = Modifier
                                .width(if (isLead) 180.dp else 120.dp)
                                .height(if (isLead) 120.dp else 80.dp)
                                .clickable { onOpenGallery(review.photoUrls, url) },
                            shape = MaterialTheme.shapes.medium
                        ) {
                            Image(
                                painter = rememberAsyncImagePainter(url),
                                contentDescription = null,
                                contentScale = ContentScale.Crop,
                                modifier = Modifier.fillMaxSize()
                            )
                        }
                    }
                }
            }

            review.replyText?.takeIf { it.isNotBlank() }?.let { reply ->
                Spacer(modifier = Modifier.height(8.dp))
                Card(
                    shape = RoundedCornerShape(12.dp),
                    border = BorderStroke(1.dp, MaterialTheme.colorScheme.primary.copy(alpha = 0.4f)),
                    colors = CardDefaults.cardColors(
                        containerColor = MaterialTheme.colorScheme.surfaceVariant
                    )
                ) {
                    Column(
                        modifier = Modifier.padding(12.dp),
                        verticalArrangement = Arrangement.spacedBy(6.dp)
                    ) {
                        Row(
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically,
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Surface(
                                shape = RoundedCornerShape(10.dp),
                                color = MaterialTheme.colorScheme.primary.copy(alpha = 0.16f)
                            ) {
                                Text(
                                    "İşletme Sahibi Yanıtı",
                                    modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp),
                                    style = MaterialTheme.typography.labelSmall,
                                    color = MaterialTheme.colorScheme.primary
                                )
                            }
                            val replyDate = formatReviewDate(review.replyCreatedAt) ?: review.replyCreatedAt
                            if (!replyDate.isNullOrBlank()) {
                                Text(
                                    replyDate,
                                    style = MaterialTheme.typography.labelSmall,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                            }
                        }
                        Text(reply, style = MaterialTheme.typography.bodySmall)
                    }
                }
            }

            if (canReply) {
                TextButton(onClick = { onReply(review) }) {
                    Text("Yanıtla")
                }
            }
        }
    }
}

@Composable
private fun ReviewComposerCard(
    rating: Int,
    onRatingChange: (Int) -> Unit,
    text: String,
    onTextChange: (String) -> Unit,
    extraKeys: List<String>,
    extraAnswers: Map<String, String>,
    onOpenExtras: () -> Unit,
    photoUris: List<Uri>,
    onAddPhotos: (List<Uri>) -> Unit,
    onRemovePhoto: (Uri) -> Unit,
    onSubmit: () -> Unit,
    submitMessage: String?,
    isSubmitting: Boolean
) {
    val photoPicker = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.GetMultipleContents()
    ) { uris ->
        if (uris.isNotEmpty()) {
            onAddPhotos(uris)
        }
    }

    Card(
        modifier = Modifier.fillMaxWidth(),
        elevation = CardDefaults.cardElevation(1.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
            Text("Yorum Yap", style = MaterialTheme.typography.titleMedium)
            RatingSelector(rating = rating, onRatingChange = onRatingChange)
            TextField(
                value = text,
                onValueChange = onTextChange,
                modifier = Modifier.fillMaxWidth(),
                placeholder = { Text("Deneyimini paylaş...") },
                maxLines = 5
            )
            if (extraKeys.isNotEmpty()) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        "Deneyim detayları",
                        style = MaterialTheme.typography.labelMedium
                    )
                    TextButton(onClick = onOpenExtras) {
                        Text("Düzenle (${extraAnswers.size}/${extraKeys.size})")
                    }
                }
            }
            Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                Text(
                    "Fotoğraflar (${photoUris.size}/10)",
                    style = MaterialTheme.typography.labelMedium
                )
                LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    items(photoUris) { uri ->
                        Box {
                            Card(
                                modifier = Modifier
                                    .width(96.dp)
                                    .height(96.dp)
                            ) {
                                Image(
                                    painter = rememberAsyncImagePainter(uri),
                                    contentDescription = null,
                                    contentScale = ContentScale.Crop,
                                    modifier = Modifier.fillMaxSize()
                                )
                            }
                            IconButton(
                                onClick = { onRemovePhoto(uri) },
                                modifier = Modifier.align(Alignment.TopEnd)
                            ) {
                                Icon(
                                    Icons.Default.Close,
                                    contentDescription = "Kaldır",
                                    tint = Color.White
                                )
                            }
                        }
                    }
                    if (photoUris.size < 10) {
                        item {
                            Card(
                                modifier = Modifier
                                    .width(96.dp)
                                    .height(96.dp)
                                    .clickable { photoPicker.launch("image/*") },
                                colors = CardDefaults.cardColors(
                                    containerColor = MaterialTheme.colorScheme.surfaceVariant
                                )
                            ) {
                                Box(contentAlignment = Alignment.Center, modifier = Modifier.fillMaxSize()) {
                                    Text("+", style = MaterialTheme.typography.titleLarge)
                                }
                            }
                        }
                    }
                }
            }
            Button(
                onClick = onSubmit,
                enabled = rating > 0 && text.isNotBlank() && !isSubmitting
            ) {
                Text(if (isSubmitting) "Gönderiliyor..." else "Yorumu Gönder")
            }
            submitMessage?.let { message ->
                Text(
                    message,
                    style = MaterialTheme.typography.labelMedium,
                    color = if (message.contains("gönderildi", true)) Color(0xFF2E7D32) else Color(0xFFC62828)
                )
            }
        }
    }
}

@Composable
private fun RatingSelector(rating: Int, onRatingChange: (Int) -> Unit) {
    Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
        (1..5).forEach { value ->
            IconButton(onClick = { onRatingChange(value) }) {
                Icon(
                    if (value <= rating) Icons.Default.Star else Icons.Default.StarBorder,
                    contentDescription = "$value yıldız",
                    tint = Color(0xFFFFC107)
                )
            }
        }
    }
}

@Composable
private fun ReviewExtraDialog(
    keys: List<String>,
    values: Map<String, String>,
    onDismiss: () -> Unit,
    onSave: (Map<String, String>) -> Unit
) {
    val draft = remember(values, keys) {
        mutableStateOf(keys.associateWith { values[it].orEmpty() })
    }
    Dialog(
        onDismissRequest = onDismiss,
        properties = DialogProperties(usePlatformDefaultWidth = false)
    ) {
        Box(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp)
        ) {
            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .fillMaxHeight(0.9f)
                    .heightIn(min = 360.dp, max = 720.dp),
                shape = MaterialTheme.shapes.large,
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxHeight()
                        .padding(20.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    Text("Deneyim Soruları", style = MaterialTheme.typography.titleMedium)
                    Column(
                        modifier = Modifier
                            .weight(1f)
                            .padding(end = 4.dp)
                            .verticalScroll(rememberScrollState()),
                        verticalArrangement = Arrangement.spacedBy(10.dp)
                    ) {
                        keys.forEach { key ->
                            TextField(
                                value = draft.value[key].orEmpty(),
                                onValueChange = { newValue ->
                                    draft.value = draft.value.toMutableMap().apply { put(key, newValue) }
                                },
                                label = { Text(key) },
                                modifier = Modifier.fillMaxWidth()
                            )
                        }
                    }
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.End
                    ) {
                        TextButton(onClick = onDismiss) { Text("Vazgeç") }
                        TextButton(onClick = { onSave(draft.value) }) { Text("Kaydet") }
                    }
                }
            }
        }
    }
}

@Composable
private fun ReviewReplyDialog(
    review: CombinedReview,
    replyText: String,
    onReplyChange: (String) -> Unit,
    onDismiss: () -> Unit,
    onSubmit: () -> Unit
) {
    Dialog(onDismissRequest = onDismiss) {
        Card(
            modifier = Modifier
                .fillMaxWidth()
                .padding(8.dp),
            shape = MaterialTheme.shapes.large
        ) {
            Column(
                modifier = Modifier.padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                Text("İşletme Sahibi Yanıtı", style = MaterialTheme.typography.titleMedium)
                Text(
                    review.text ?: "",
                    style = MaterialTheme.typography.bodySmall,
                    maxLines = 4,
                    overflow = TextOverflow.Ellipsis
                )
                TextField(
                    value = replyText,
                    onValueChange = onReplyChange,
                    modifier = Modifier.fillMaxWidth(),
                    placeholder = { Text("Yanıtınızı yazın...") },
                    maxLines = 5
                )
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.End
                ) {
                    TextButton(onClick = onDismiss) { Text("Vazgeç") }
                    TextButton(onClick = onSubmit) { Text("Yanıtla") }
                }
            }
        }
    }
}

@Composable
private fun ReviewSummaryCard(summary: RatingSummary) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        elevation = CardDefaults.cardElevation(1.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text("Toplam Puan", style = MaterialTheme.typography.titleMedium)
            Row(verticalAlignment = Alignment.CenterVertically) {
                Text(
                    summary.average.toString(),
                    style = MaterialTheme.typography.displaySmall,
                    fontWeight = FontWeight.Bold
                )
                Spacer(modifier = Modifier.width(12.dp))
                Column {
                    StarRating(rating = summary.average)
                    Text(
                        "${summary.total} yorum",
                        style = MaterialTheme.typography.labelSmall
                    )
                }
            }
            Spacer(modifier = Modifier.height(12.dp))
            summary.distribution.entries.sortedByDescending { it.key }.forEach { (rating, count) ->
                RatingProgressRow(rating = rating, count = count, total = summary.total)
            }
        }
    }
}

@Composable
private fun RatingProgressRow(rating: Int, count: Int, total: Int) {
    Row(
        verticalAlignment = Alignment.CenterVertically,
        modifier = Modifier.padding(vertical = 4.dp)
    ) {
        Text("$rating", modifier = Modifier.width(18.dp))
        StarRating(rating = rating.toDouble())
        Spacer(modifier = Modifier.width(8.dp))
        Box(
            modifier = Modifier
                .weight(1f)
                .height(8.dp)
                .background(MaterialTheme.colorScheme.surfaceVariant)
        ) {
            val ratio = if (total == 0) 0f else count.toFloat() / total
            Box(
                modifier = Modifier
                    .fillMaxHeight()
                    .fillMaxWidth(ratio)
                    .background(MaterialTheme.colorScheme.primary)
            )
        }
        Spacer(modifier = Modifier.width(8.dp))
        Text("$count", style = MaterialTheme.typography.labelSmall)
    }
}

@Composable
private fun StarRating(rating: Double) {
    val filled = rating.toInt().coerceIn(0, 5)
    Row(verticalAlignment = Alignment.CenterVertically) {
        repeat(5) { index ->
            val icon = if (index < filled) Icons.Default.Star else Icons.Default.StarBorder
            Icon(
                icon,
                contentDescription = null,
                tint = Color(0xFFFFC107),
                modifier = Modifier.size(16.dp)
            )
        }
    }
}

@Composable
private fun StatusBadge(status: PlaceStatus) {
    val color = if (status.isOpen) Color(0xFF2E7D32) else Color(0xFFC62828)
    Box(
        modifier = Modifier
            .background(color.copy(alpha = 0.15f), shape = MaterialTheme.shapes.small)
            .padding(horizontal = 8.dp, vertical = 4.dp)
    ) {
        Text(
            status.label,
            color = color,
            style = MaterialTheme.typography.labelSmall,
            maxLines = 1,
            overflow = TextOverflow.Ellipsis
        )
    }
}

@Composable
private fun ReviewAvatar(review: CombinedReview) {
    val imageUrl = review.profilePhotoUrl
    if (!imageUrl.isNullOrBlank()) {
        Card(
            modifier = Modifier
                .width(48.dp)
                .height(48.dp),
            shape = MaterialTheme.shapes.medium
        ) {
            Image(
                painter = rememberAsyncImagePainter(imageUrl),
                contentDescription = review.authorName,
                contentScale = ContentScale.Crop,
                modifier = Modifier.fillMaxSize()
            )
        }
    } else {
        Card(
            modifier = Modifier
                .width(48.dp)
                .height(48.dp),
            shape = MaterialTheme.shapes.medium
        ) {
            Box(
                modifier = Modifier.fillMaxSize(),
                contentAlignment = Alignment.Center
            ) {
                Text(text = review.authorName?.take(1)?.uppercase() ?: "?")
            }
        }
    }
}

@Composable
fun HtmlText(
    html: String,
    modifier: Modifier = Modifier,
    fontSizeSp: Float = 14f,
    lineSpacingExtra: Float = 8f,
    lineSpacingMultiplier: Float = 1.2f,
    maxLines: Int = Int.MAX_VALUE
) {
    AndroidView(
        modifier = modifier,
        factory = { context ->
            TextView(context).apply {
                movementMethod = LinkMovementMethod.getInstance()
                isFocusable = false
                isClickable = false
                setLineSpacing(lineSpacingExtra, lineSpacingMultiplier)
                setTextSize(TypedValue.COMPLEX_UNIT_SP, fontSizeSp)
                this.maxLines = maxLines
                linksClickable = true
            }
        },
        update = { view ->
            val imageGetter = HtmlImageGetter(view.context)
            val spanned = HtmlCompat.fromHtml(
                html.trim(),
                HtmlCompat.FROM_HTML_MODE_LEGACY,
                imageGetter,
                null
            )
            view.text = spanned
        }
    )
}

class HtmlImageGetter(
    private val context: Context
) : Html.ImageGetter {
    override fun getDrawable(source: String): Drawable? {
        return try {
            val url = URL(source)
            val connection = url.openConnection()
            connection.connect()
            val input = connection.getInputStream()
            val bitmap = BitmapFactory.decodeStream(input)
            val drawable = BitmapDrawable(context.resources, bitmap)
            drawable.setBounds(0, 0, drawable.intrinsicWidth, drawable.intrinsicHeight)
            drawable
        } catch (e: Exception) {
            e.printStackTrace()
            null
        }
    }
}

@Composable
private fun ReviewSortRow(selected: ReviewSort, onSelect: (ReviewSort) -> Unit) {
    Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
        ReviewSort.entries.forEach { sort ->
            TextButton(onClick = { onSelect(sort) }) {
                Text(if (sort == selected) "✓ ${sort.label}" else sort.label)
            }
        }
    }
}

@Composable
private fun SocialLinkCard(link: PlaceSocialDto) {
    val label = link.label.ifBlank { link.url }
    val icon = socialIconForLabel(label)
    val context = LocalContext.current
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .clickable {
                val uri = Uri.parse(link.url)
                val intent = Intent(Intent.ACTION_VIEW, uri)
                intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                context.startActivity(intent)
            },
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.surfaceVariant
        ),
        elevation = CardDefaults.cardElevation(0.dp)
    ) {
        Row(
            modifier = Modifier.padding(horizontal = 16.dp, vertical = 12.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Text(icon, style = MaterialTheme.typography.titleMedium)
            Column(modifier = Modifier.weight(1f)) {
                Text(label, style = MaterialTheme.typography.titleSmall)
                Text(
                    link.url,
                    style = MaterialTheme.typography.labelSmall,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )
            }
            Icon(Icons.Default.Link, contentDescription = null)
        }
    }
}

@Composable
private fun ReviewExtraCard(label: String, value: String) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.surfaceVariant
        ),
        border = BorderStroke(
            1.dp,
            MaterialTheme.colorScheme.outline
        ),
        elevation = CardDefaults.cardElevation(0.dp)
    ) {
        Row(
            modifier = Modifier.padding(12.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.SpaceBetween
        ) {
            Column(
                modifier = Modifier.weight(1f),
                verticalArrangement = Arrangement.spacedBy(4.dp)
            ) {
                Text(
                    label,
                    style = MaterialTheme.typography.labelMedium,
                    color = MaterialTheme.colorScheme.primary
                )
                Text(
                    value,
                    style = MaterialTheme.typography.bodyMedium,
                    fontWeight = FontWeight.Medium
                )
            }
        }
    }
}

@RequiresApi(Build.VERSION_CODES.O)
@Composable
private fun BusyHoursChart(busyHours: Map<String, List<BusyHour>>) {
    val today = dayName(LocalDateTime.now().dayOfWeek.value)
    val rawEntries = busyHours[today] ?: busyHours.values.firstOrNull().orEmpty()
    val entries = buildFullDayHours(rawEntries)
    Card(
        modifier = Modifier.fillMaxWidth(),
        elevation = CardDefaults.cardElevation(1.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text("Yoğunluk Saatleri ($today)", style = MaterialTheme.typography.titleMedium)
            if (entries.isEmpty()) {
                Text("Veri bulunamadı.")
            } else {
                val max = entries.maxOf { it.value }.coerceAtLeast(1)
                LazyRow(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                    items(entries.size) { index ->
                        val entry = entries[index]
                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                            Text(
                                "${entry.value}%",
                                style = MaterialTheme.typography.labelSmall
                            )
                            Box(
                                modifier = Modifier
                                    .width(18.dp)
                                    .height((140 * entry.value / max).coerceAtLeast(8).dp)
                                    .background(MaterialTheme.colorScheme.primary)
                            )
                            Text(
                                entry.hour,
                                style = MaterialTheme.typography.labelSmall
                            )
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun ServiceGroupCard(group: ServiceGroup) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        elevation = CardDefaults.cardElevation(1.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text(
                "${group.icon} ${group.title}",
                style = MaterialTheme.typography.titleMedium
            )
            Spacer(modifier = Modifier.height(8.dp))
            group.items.forEach { item ->
                Text("• $item", style = MaterialTheme.typography.bodySmall)
            }
        }
    }
}

@OptIn(ExperimentalFoundationApi::class)
@Composable
private fun GalleryDialog(
    state: GalleryDialogState,
    onDismiss: () -> Unit
) {
    if (state.items.isEmpty()) {
        LaunchedEffect(Unit) { onDismiss() }
        return
    }

    val pagerState = rememberPagerState(
        initialPage = state.initialIndex.coerceIn(0, state.items.lastIndex),
        pageCount = { state.items.size }
    )
    val scope = rememberCoroutineScope()

    var offsetY by remember { mutableFloatStateOf(0f) }
    val alpha by animateFloatAsState(
        targetValue = (1f - (abs(offsetY) / 1000f)).coerceIn(0.5f, 1f),
        label = "alpha"
    )

    Dialog(
        onDismissRequest = onDismiss,
        properties = DialogProperties(
            usePlatformDefaultWidth = false,
            decorFitsSystemWindows = false
        )
    ) {
        Box(
            modifier = Modifier
                .fillMaxSize()
                .background(Color.Black.copy(alpha = alpha))
                .windowInsetsPadding(WindowInsets.statusBars)
        ) {
            Image(
                painter = rememberAsyncImagePainter(state.items[pagerState.currentPage].url),
                contentDescription = null,
                modifier = Modifier
                    .fillMaxSize()
                    .blur(50.dp)
                    .alpha(0.3f),
                contentScale = ContentScale.Crop
            )

            HorizontalPager(
                state = pagerState,
                modifier = Modifier
                    .fillMaxSize()
                    .graphicsLayer { translationY = offsetY }
                    .pointerInput(Unit) {
                        detectVerticalDragGestures(
                            onVerticalDrag = { _, dragAmount -> offsetY += dragAmount },
                            onDragEnd = {
                                if (abs(offsetY) > 300f) onDismiss() else offsetY = 0f
                            }
                        )
                    },
                pageSpacing = 16.dp
            ) { page ->
                val item = state.items[page]
                Box(
                    modifier = Modifier.fillMaxSize(),
                    contentAlignment = Alignment.Center
                ) {
                    if (item.isVideo) {
                        GalleryVideoPlayer(
                            url = item.url,
                            modifier = Modifier.fillMaxWidth().aspectRatio(16f / 9f)
                        )
                    } else {
                        Image(
                            painter = rememberAsyncImagePainter(item.url),
                            contentDescription = null,
                            contentScale = ContentScale.Fit,
                            modifier = Modifier.fillMaxSize()
                        )
                    }
                }
            }

            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .background(
                        Brush.verticalGradient(
                            listOf(Color.Black.copy(0.7f), Color.Transparent)
                        )
                    )
                    .padding(horizontal = 16.dp, vertical = 24.dp)
                    .align(Alignment.TopCenter),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Column {
                    Text(
                        "Galeri",
                        color = Color.White,
                        style = MaterialTheme.typography.titleLarge.copy(fontWeight = FontWeight.Bold)
                    )
                    Text(
                        "${pagerState.currentPage + 1} / ${state.items.size}",
                        color = Color.White.copy(alpha = 0.7f),
                        style = MaterialTheme.typography.labelMedium
                    )
                }

                IconButton(
                    onClick = onDismiss,
                    modifier = Modifier.background(Color.White.copy(0.2f), CircleShape)
                ) {
                    Icon(Icons.Default.Close, contentDescription = "Kapat", tint = Color.White)
                }
            }

            if (pagerState.currentPage > 0) {
                NavigationArrow(Icons.Default.ArrowBack, Alignment.CenterStart) {
                    scope.launch { pagerState.animateScrollToPage(pagerState.currentPage - 1) }
                }
            }

            if (pagerState.currentPage < state.items.lastIndex) {
                NavigationArrow(Icons.Default.ArrowForward, Alignment.CenterEnd) {
                    scope.launch { pagerState.animateScrollToPage(pagerState.currentPage + 1) }
                }
            }

            Row(
                Modifier
                    .height(50.dp)
                    .fillMaxWidth()
                    .align(Alignment.BottomCenter),
                horizontalArrangement = Arrangement.Center
            ) {
                repeat(state.items.size) { iteration ->
                    val color =
                        if (pagerState.currentPage == iteration) Color.White else Color.White.copy(alpha = 0.3f)
                    Box(
                        modifier = Modifier
                            .padding(4.dp)
                            .clip(CircleShape)
                            .background(color)
                            .size(if (pagerState.currentPage == iteration) 8.dp else 6.dp)
                    )
                }
            }
        }
    }
}

@Composable
private fun BoxScope.NavigationArrow(
    icon: ImageVector,
    alignment: Alignment,
    onClick: () -> Unit
) {
    IconButton(
        onClick = onClick,
        modifier = Modifier
            .align(alignment)
            .padding(12.dp)
            .size(44.dp)
            .background(Color.Black.copy(alpha = 0.3f), CircleShape)
    ) {
        Icon(icon, null, tint = Color.White)
    }
}

@Composable
private fun GalleryVideoPlayer(url: String, modifier: Modifier = Modifier) {
    val context = LocalContext.current
    val exoPlayer = remember(url) {
        ExoPlayer.Builder(context).build().apply {
            setMediaItem(MediaItem.fromUri(url))
            prepare()
            playWhenReady = true
        }
    }

    DisposableEffect(exoPlayer) {
        onDispose {
            exoPlayer.release()
        }
    }

    AndroidView(
        factory = { ctx ->
            PlayerView(ctx).apply {
                player = exoPlayer
                useController = true
            }
        },
        modifier = modifier
    )
}

// ----- DATA CLASSES -----

private data class CombinedReview(
    val id: String,
    val source: String,
    val reviewRef: String,
    val authorName: String?,
    val rating: Int?,
    val text: String?,
    val createdAt: String?,
    val sortTimestamp: Long?,
    val profilePhotoUrl: String?,
    val textExtra: Map<String, String>,
    val photoUrls: List<String>,
    val replyText: String? = null,
    val replyRole: String? = null,
    val replyCreatedAt: String? = null
)

private data class ReviewReplyTarget(
    val review: CombinedReview
)

private data class UserPhotoGroup(
    val authorName: String?,
    val profilePhotoUrl: String?,
    val createdAt: String?,
    val photos: List<String>
)

private enum class ReviewSort(val label: String) {
    NEWEST("En yeni"),
    OLDEST("En eski"),
    HIGHEST("En yüksek"),
    LOWEST("En düşük")
}

private data class RatingSummary(
    val average: Double,
    val total: Int,
    val distribution: Map<Int, Int>
)

private data class BusyHour(
    val hour: String,
    val value: Int
)

private data class GalleryMedia(
    val url: String,
    val isVideo: Boolean
)

private data class GalleryDialogState(
    val items: List<GalleryMedia>,
    val initialIndex: Int
)

private data class ServiceGroup(
    val key: String,
    val icon: String,
    val title: String,
    val items: List<String>
)

private data class ServiceSection(
    val title: String,
    val items: List<ServiceItem>
)

private data class ServiceItem(
    val title: String,
    val description: String?,
    val priceText: String?,
    val buttonLabel: String?,
    val buttonUrl: String?,
    val imageUrl: String?
)

private data class PlaceStatus(
    val label: String,
    val isOpen: Boolean
)

@Composable
private fun ServiceSectionCard(section: ServiceSection) {
    val context = LocalContext.current
    Card(
        modifier = Modifier.fillMaxWidth(),
        elevation = CardDefaults.cardElevation(1.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text(section.title, style = MaterialTheme.typography.titleMedium)
            Spacer(modifier = Modifier.height(8.dp))
            section.items.forEach { item ->
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(vertical = 6.dp),
                    border = BorderStroke(
                        1.dp,
                        MaterialTheme.colorScheme.outline
                    ),
                    elevation = CardDefaults.cardElevation(0.dp)
                ) {
                    Row(
                        modifier = Modifier.padding(12.dp),
                        horizontalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        if (!item.imageUrl.isNullOrBlank()) {
                            Image(
                                painter = rememberAsyncImagePainter(item.imageUrl),
                                contentDescription = item.title,
                                contentScale = ContentScale.Crop,
                                modifier = Modifier
                                    .width(72.dp)
                                    .height(72.dp)
                            )
                        }
                        Column(
                            modifier = Modifier.weight(1f),
                            verticalArrangement = Arrangement.spacedBy(4.dp)
                        ) {
                            Row(
                                horizontalArrangement = Arrangement.spacedBy(6.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Icon(Icons.Default.CheckCircle, contentDescription = null)
                                Text(
                                    item.title,
                                    style = MaterialTheme.typography.titleSmall
                                )
                            }
                            item.description?.let { desc ->
                                Text(
                                    desc,
                                    style = MaterialTheme.typography.bodySmall,
                                    maxLines = 3,
                                    overflow = TextOverflow.Ellipsis
                                )
                            }
                            item.priceText?.let { price ->
                                Text(
                                    price,
                                    style = MaterialTheme.typography.bodyMedium,
                                    fontWeight = FontWeight.SemiBold
                                )
                            }
                            val buttonLabel =
                                item.buttonLabel?.takeIf { it.isNotBlank() } ?: "Detay"
                            Button(
                                onClick = {
                                    val uri = Uri.parse(item.buttonUrl)
                                    val intent = Intent(Intent.ACTION_VIEW, uri)
                                    intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                                    context.startActivity(intent)
                                },
                                enabled = !item.buttonUrl.isNullOrBlank()
                            ) {
                                Icon(Icons.Default.ArrowForward, contentDescription = null)
                                Spacer(modifier = Modifier.width(6.dp))
                                Text(buttonLabel)
                            }
                        }
                    }
                }
            }
        }
    }
}

// ----- HELPERS -----

@RequiresApi(Build.VERSION_CODES.O)
private fun buildCombinedReviews(
    googleReviews: List<GoogleReviewDto>,
    userReviews: List<UserReviewDto>
): List<CombinedReview> {
    val google = googleReviews.mapIndexed { index, review ->
        val reviewRef = buildGoogleReviewRef(review)
        CombinedReview(
            id = "google-$index",
            source = "google",
            reviewRef = reviewRef,
            authorName = review.author_name,
            rating = review.rating,
            text = review.text,
            createdAt = review.created_at ?: review.relative_time,
            sortTimestamp = parseDate(review.created_at ?: review.relative_time),
            profilePhotoUrl = review.profile_photo_url,
            textExtra = review.text_extra ?: emptyMap(),
            photoUrls = review.review_photo_urls ?: emptyList()
        )
    }
    val user = userReviews.mapIndexed { index, review ->
        CombinedReview(
            id = "user-$index",
            source = "user",
            reviewRef = "user-$index",
            authorName = review.author_name,
            rating = review.rating,
            text = review.review_text,
            createdAt = review.created_at,
            sortTimestamp = parseDate(review.created_at),
            profilePhotoUrl = review.profile_photo_url,
            textExtra = review.text_extra ?: emptyMap(),
            photoUrls = review.review_photo_urls ?: emptyList(),
            replyText = review.reply?.reply_text,
            replyRole = review.reply?.replied_role,
            replyCreatedAt = review.reply?.updated_at ?: review.reply?.created_at
        )
    }
    return google + user
}

private fun buildUserPhotoGroups(reviews: List<CombinedReview>): List<UserPhotoGroup> {
    return reviews
        .filter { it.photoUrls.isNotEmpty() }
        .groupBy { review -> review.authorName to review.profilePhotoUrl }
        .map { (key, grouped) ->
            val sorted = grouped.sortedByDescending { it.sortTimestamp ?: 0L }
            val photos = sorted.flatMap { it.photoUrls }.distinct()
            UserPhotoGroup(
                authorName = key.first,
                profilePhotoUrl = key.second,
                createdAt = sorted.firstOrNull()?.createdAt,
                photos = photos
            )
        }
        .sortedByDescending { group ->
            reviews.firstOrNull { review ->
                review.authorName == group.authorName &&
                        review.profilePhotoUrl == group.profilePhotoUrl
            }?.sortTimestamp ?: 0L
        }
}

private fun sortCombinedReviews(
    reviews: List<CombinedReview>,
    sort: ReviewSort
): List<CombinedReview> {
    return when (sort) {
        ReviewSort.NEWEST -> reviews.sortedByDescending { it.sortTimestamp ?: 0L }
        ReviewSort.OLDEST -> reviews.sortedBy { it.sortTimestamp ?: Long.MAX_VALUE }
        ReviewSort.HIGHEST -> reviews.sortedByDescending { it.rating ?: 0 }
        ReviewSort.LOWEST -> reviews.sortedBy { it.rating ?: Int.MAX_VALUE }
    }
}

private fun buildRatingSummary(reviews: List<CombinedReview>): RatingSummary {
    if (reviews.isEmpty()) {
        return RatingSummary(0.0, 0, emptyMap())
    }
    val distribution = (1..5).associateWith { rating ->
        reviews.count { it.rating == rating }
    }
    val avg = reviews.mapNotNull { it.rating }.average().takeIf { !it.isNaN() } ?: 0.0
    val avgRounded = kotlin.math.round(avg * 10.0) / 10.0
    return RatingSummary(
        average = avgRounded,
        total = reviews.size,
        distribution = distribution
    )
}

private suspend fun fetchPlaceDetailFallback(placeId: Long): PlaceDetailResponse? {
    return withContext(Dispatchers.IO) {
        runCatching {
            val url = URL("https://guidexy.com/api-app/place-detail.php?id=$placeId")
            val json = url.readText()
            Gson().fromJson(json, PlaceDetailResponse::class.java)
        }.getOrNull()
    }
}

@RequiresApi(Build.VERSION_CODES.O)
private fun parseDate(value: String?): Long? {
    if (value.isNullOrBlank()) {
        return null
    }
    val numeric = value.trim()
    numeric.toLongOrNull()?.let { raw ->
        return if (raw > 10_000_000_000L) raw else raw * 1000
    }
    val patterns = listOf("yyyy-MM-dd HH:mm:ss", "yyyy-MM-dd HH:mm")
    patterns.forEach { pattern ->
        val parsed = runCatching {
            val formatter = DateTimeFormatter.ofPattern(pattern)
            val dateTime = LocalDateTime.parse(value, formatter)
            dateTime.atZone(ZoneId.systemDefault()).toInstant().toEpochMilli()
        }.getOrNull()
        if (parsed != null) {
            return parsed
        }
    }
    return null
}

@RequiresApi(Build.VERSION_CODES.O)
private fun formatReviewDate(value: String?): String? {
    if (value.isNullOrBlank()) {
        return null
    }
    val outputFormatter = DateTimeFormatter.ofPattern("dd.MM.yyyy HH:mm")
    val inputPatterns = listOf(
        "yyyy-MM-dd HH:mm:ss",
        "yyyy-MM-dd HH:mm"
    )
    for (pattern in inputPatterns) {
        val parsed = runCatching {
            LocalDateTime.parse(value, DateTimeFormatter.ofPattern(pattern))
        }.getOrNull()
        if (parsed != null) {
            return parsed.format(outputFormatter)
        }
    }
    return null
}

private fun formatTime(value: String?): String? {
    if (value.isNullOrBlank()) {
        return value
    }
    val parts = value.trim().split(":")
    return if (parts.size >= 2) {
        "${parts[0].padStart(2, '0')}:${parts[1].padStart(2, '0')}"
    } else {
        value
    }
}

private fun isVideoUrl(value: String?): Boolean {
    val url = value?.lowercase() ?: return false
    return url.endsWith(".mp4") ||
        url.endsWith(".mov") ||
        url.endsWith(".webm") ||
        url.endsWith(".mkv")
}

private fun formatStatusText(value: String?): String? {
    return value?.replace(Regex("\\b(\\d{1,2}:\\d{2}):\\d{2}\\b"), "$1")
}

private fun dayLabel(day: Int): String {
    return when (day) {
        1 -> "Pazartesi"
        2 -> "Salı"
        3 -> "Çarşamba"
        4 -> "Perşembe"
        5 -> "Cuma"
        6 -> "Cumartesi"
        7 -> "Pazar"
        else -> "Gün $day"
    }
}

private fun dayName(day: Int): String {
    return when (day) {
        1 -> "Pazartesi"
        2 -> "Salı"
        3 -> "Çarşamba"
        4 -> "Perşembe"
        5 -> "Cuma"
        6 -> "Cumartesi"
        7 -> "Pazar"
        else -> "Pazartesi"
    }
}

private fun parseBusyHours(raw: Any?): Map<String, List<BusyHour>> {
    if (raw == null) return emptyMap()

    return when (raw) {

        is String -> { // boş, null veya düzgün JSON string
            if (raw.isBlank()) return emptyMap()
            parseBusyJsonString(raw)
        }

        is Map<*, *> -> { // doğrudan JSON parse edilmiş format
            parseBusyJsonObject(raw)
        }

        else -> emptyMap()
    }
}

private fun parseBusyJsonString(raw: String): Map<String, List<BusyHour>> {
    return runCatching {
        val type = object : TypeToken<Map<String, List<Map<String, Any>>>>() {}.type
        val parsed: Map<String, List<Map<String, Any>>> = Gson().fromJson(raw, type)
        mapBusy(parsed)
    }.getOrDefault(emptyMap())
}

private fun parseBusyJsonObject(raw: Map<*, *>): Map<String, List<BusyHour>> {
    return runCatching {
        @Suppress("UNCHECKED_CAST")
        val parsed = raw as Map<String, List<Map<String, Any>>>
        mapBusy(parsed)
    }.getOrDefault(emptyMap())
}


private fun mapBusy(parsed: Map<String, List<Map<String, Any>>>): Map<String, List<BusyHour>> {
    return parsed.mapValues { (_, rows) ->
        rows.mapNotNull { row ->
            val hourValue = (row["saat"] ?: row["hour"] ?: row["time"])?.toString() ?: return@mapNotNull null
            val rawBusy = row["busy"] ?: row["yogunluk"] ?: row["yoğunluk"] ?: row["load"] ?: row["value"]
            val busy = rawBusy?.toString()?.replace("%", "")?.toIntOrNull() ?: 0
            val hourLabel = parseHourValue(hourValue) ?: return@mapNotNull null
            BusyHour(hour = hourLabel, value = busy)
        }
    }
}

private fun permalink(text: String): String {
    var s = text.trim()
    if (s.isEmpty()) return ""

    val trMap = mapOf(
        'Ç' to 'C', 'Ş' to 'S', 'Ğ' to 'G', 'Ü' to 'U', 'İ' to 'I', 'Ö' to 'O',
        'ç' to 'c', 'ş' to 's', 'ğ' to 'g', 'ü' to 'u', 'ı' to 'i', 'ö' to 'o'
    )
    s = buildString {
        for (c in s) append(trMap[c] ?: c)
    }

    s = Normalizer.normalize(s, Normalizer.Form.NFD)
        .replace("[^\\p{ASCII}]".toRegex(), "")

    s = s.lowercase()
    s = s.replace("[^a-z0-9]+".toRegex(), "-")
    s = s.replace("-+".toRegex(), "-")

    return s.trim('-')
}

private fun parseHourValue(value: String): String? {
    val digits = value.trim().takeWhile { it.isDigit() }
    val hour = digits.toIntOrNull() ?: return null
    return String.format("%02d", hour.coerceIn(0, 23))
}

private fun buildFullDayHours(entries: List<BusyHour>): List<BusyHour> {
    if (entries.isEmpty()) return emptyList()
    val lookup = entries.associateBy { it.hour }
    return (0..23).map { hour ->
        val label = String.format("%02d", hour)
        val value = lookup[label]?.value ?: 0
        BusyHour(hour = label, value = value)
    }
}

private fun groupServices(knowsAbout: List<PlaceKnowsAboutDto>): List<ServiceGroup> {
    val config = mapOf(
        "accessibility" to Pair("♿", "Erişilebilirlik"),
        "services" to Pair("🛎️", "Hizmetler"),
        "highlights" to Pair("⭐", "Öne Çıkanlar"),
        "payment" to Pair("💳", "Ödeme & Fiyat"),
        "family" to Pair("👨‍👩‍👧", "Aile & Çocuk"),
        "transport" to Pair("🚗", "Ulaşım & Park"),
        "pet" to Pair("🐾", "Evcil Hayvan"),
        "food" to Pair("🍽️", "Yemek Seçenekleri"),
        "offered" to Pair("🎁", "Sunulanlar"),
        "popular" to Pair("🔥", "Popüler Olma Nedeni")
    )
    return knowsAbout.groupBy { it.category }.mapNotNull { (category, items) ->
        val info = config[category] ?: return@mapNotNull null
        ServiceGroup(
            key = category,
            icon = info.first,
            title = info.second,
            items = items.map { it.value }
        )
    }
}

private fun socialIconForLabel(label: String): String {
    val l = label.lowercase()
    return when {
        l.contains("instagram") -> "📸"
        l.contains("facebook") -> "📘"
        l.contains("twitter") || l.contains("x") -> "𝕏"
        l.contains("youtube") -> "▶️"
        l.contains("tiktok") -> "🎵"
        l.contains("linkedin") -> "💼"
        l.contains("whatsapp") -> "💬"
        else -> "🌐"
    }
}

private fun buildServiceSections(
    services: List<PlaceServiceDto>,
    categories: List<PlaceServiceCategoryDto>,
    items: List<PlaceServiceItemDto>,
    prices: List<PlaceServiceItemPriceDto>
): List<ServiceSection> {
    val pricesByItem = prices.groupBy { it.item_id }
    return services.flatMap { service ->
        val serviceCategories = categories.filter { it.service_id == service.id }
        if (serviceCategories.isEmpty()) {
            val serviceItems = items.filter { it.category_id == service.id }
            if (serviceItems.isEmpty()) {
                emptyList()
            } else {
                listOf(
                    ServiceSection(
                        title = service.title,
                        items = serviceItems.map { item ->
                            buildServiceItem(item, pricesByItem[item.id].orEmpty())
                        }
                    )
                )
            }
        } else {
            serviceCategories.map { category ->
                val categoryItems = items.filter { it.category_id == category.id }
                ServiceSection(
                    title = category.title,
                    items = categoryItems.map { item ->
                        buildServiceItem(item, pricesByItem[item.id].orEmpty())
                    }
                )
            }
        }
    }.filter { it.items.isNotEmpty() }
}

private fun buildServiceItem(
    item: PlaceServiceItemDto,
    prices: List<PlaceServiceItemPriceDto>
): ServiceItem {
    val priceText = when {
        prices.isNotEmpty() -> prices.joinToString(" • ") { price ->
            "${price.title} ${price.currency_symbol}${price.price}"
        }

        !item.price.isNullOrBlank() && !item.currency_symbol.isNullOrBlank() ->
            "${item.currency_symbol}${item.price}"

        else -> null
    }
    return ServiceItem(
        title = item.title,
        description = item.description,
        priceText = priceText,
        buttonLabel = item.button_label,
        buttonUrl = item.button_url,
        imageUrl = item.image_url
    )
}

private fun extractReviewExtraKeys(reviews: List<CombinedReview>): List<String> {
    return reviews
        .flatMap { it.textExtra.keys }
        .distinct()
        .sorted()
}

private fun canEditPlace(place: PlaceDetailDto, user: UserProfileDto?): Boolean {
    if (user == null) {
        return false
    }
    if (user.role == "admin") {
        return true
    }
    if (user.role == "business_owner" && place.claimed_by == user.id) {
        return true
    }
    return false
}

private fun buildGoogleReviewRef(review: GoogleReviewDto): String {
    val raw = (review.author_name ?: "") + (review.relative_time ?: "") + (review.text ?: "")
    return raw.toByteArray().let { bytes ->
        java.security.MessageDigest.getInstance("SHA-1").digest(bytes).joinToString("") { "%02x".format(it) }
    }
}

@SuppressLint("NewApi")
private fun PlaceReviewDto.toCombinedReview(): CombinedReview {
    return CombinedReview(
        id = "${source}-${review_ref}",
        source = source,
        reviewRef = review_ref,
        authorName = author_name,
        rating = rating?.toInt(),
        text = text,
        createdAt = relative_time,
        sortTimestamp = parseDate(relative_time),
        profilePhotoUrl = profile_photo_url,
        textExtra = text_extra ?: emptyMap(),
        photoUrls = review_photo_urls ?: emptyList(),
        replyText = reply?.reply_text,
        replyRole = reply?.replied_role,
        replyCreatedAt = reply?.updated_at ?: reply?.created_at
    )
}

private suspend fun uploadReviewPhotos(
    context: Context,
    placeId: Long,
    userId: Long,
    uris: List<Uri>
): List<String> {
    if (uris.isEmpty()) return emptyList()
    val uploaded = mutableListOf<String>()
    val placeBody = placeId.toString().toRequestBody("text/plain".toMediaTypeOrNull())
    val userBody = userId.toString().toRequestBody("text/plain".toMediaTypeOrNull())
    for (uri in uris) {
        val file = createTempUploadFile(context, uri) ?: continue
        val requestBody = file.readBytes().toRequestBody("image/*".toMediaTypeOrNull())
        val part = MultipartBody.Part.createFormData("file", file.name, requestBody)
        val response = runCatching {
            ApiClient.service.uploadReviewImage(placeBody, userBody, part)
        }.getOrNull()
        if (response?.url != null) {
            uploaded.add(response.url)
        }
        file.delete()
    }
    return uploaded
}

private fun createTempUploadFile(context: Context, uri: Uri): File? {
    return runCatching {
        val input = context.contentResolver.openInputStream(uri) ?: return@runCatching null
        val tempFile = File.createTempFile("upload_", ".tmp", context.cacheDir)
        FileOutputStream(tempFile).use { out ->
            input.copyTo(out)
        }
        input.close()
        tempFile
    }.getOrNull()
}

@Composable
fun SaatlerTab(
    hours: List<PlaceHoursDto>,
    openingHours: Any?
) {
    // 1) Eğer hours listesi boşsa opening_hours’u fallback olarak parse et
    val rows = remember(hours, openingHours) {
        if (hours.isNotEmpty()) {
            hours.map { hour ->
                val dayLabel = dayLabel(hour.day)
                val openTime = formatTime(hour.open_time)
                val closeTime = formatTime(hour.close_time)
                val statusText = when {
                    hour.is_closed == 1 -> "Kapalı"
                    hour.is_24h == 1 -> "24 saat açık"
                    openTime.isNullOrBlank() && closeTime.isNullOrBlank() -> "-"
                    else -> "${openTime ?: ""} - ${closeTime ?: ""}"
                }
                dayLabel to statusText
            }
        } else {
            // opening_hours fallback parse
            when (openingHours) {
                is List<*> -> openingHours.filterIsInstance<String>()
                is String -> runCatching {
                    Gson().fromJson(openingHours, Array<String>::class.java).toList()
                }.getOrNull() ?: emptyList()
                else -> emptyList()
            }.map { line ->
                val parts = line.split(":", limit = 2)
                val day = parts.getOrNull(0)?.trim() ?: "-"
                val time = parts.getOrNull(1)?.trim() ?: "-"
                day to time
            }
        }
    }

    if (rows.isEmpty()) {
        Text("Saat bilgisi bulunamadı.")
        return
    }

    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
        rows.forEach { (day, time) ->
            Card(
                modifier = Modifier.fillMaxWidth(),
                elevation = CardDefaults.cardElevation(1.dp)
            ) {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(12.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Row(
                        modifier = Modifier.weight(1f),
                        horizontalArrangement = Arrangement.spacedBy(8.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Icon(Icons.Default.Schedule, contentDescription = null)
                        Text(
                            day,
                            style = MaterialTheme.typography.bodyMedium,
                            fontWeight = FontWeight.Medium
                        )
                    }
                    Text(
                        time,
                        style = MaterialTheme.typography.bodyMedium,
                        fontWeight = FontWeight.SemiBold
                    )
                }
            }
        }
    }
}
