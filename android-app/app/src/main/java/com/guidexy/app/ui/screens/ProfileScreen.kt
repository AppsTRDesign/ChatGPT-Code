package com.guidexy.app.ui.screens

import android.widget.Toast
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.filled.StarBorder
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ScrollableTabRow
import androidx.compose.material3.Tab
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TextField
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import coil.compose.rememberAsyncImagePainter
import com.guidexy.app.R
import com.guidexy.app.data.ApiClient
import com.guidexy.app.data.AuthRequest
import com.guidexy.app.data.OwnerPlaceDto
import com.guidexy.app.data.PlaceReviewDto
import com.guidexy.app.data.ReviewReplyRequest
import com.guidexy.app.data.RegisterRequest
import com.guidexy.app.data.UserProfileDto
import com.guidexy.app.data.UserReviewItemDto
import com.guidexy.app.data.UserSession
import kotlinx.coroutines.launch
import androidx.compose.runtime.rememberCoroutineScope
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.navigation.NavController
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.toRequestBody
import java.io.File
import java.io.FileOutputStream
import java.text.SimpleDateFormat
import java.util.Locale

@Composable
fun ProfileScreen(navController: NavController) {
    val profile = remember { mutableStateOf(UserSession.currentUser) }
    val isLoggedIn = remember { mutableStateOf(UserSession.currentUser != null) }
    val statusMessage = remember { mutableStateOf<String?>(null) }
    val scope = rememberCoroutineScope()

    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
    ) {
        if (isLoggedIn.value) {
            ProfileLoggedIn(
                profile = profile.value,
                navController = navController,
                onProfileUpdated = { profile.value = it },
                onLogout = {
                    scope.launch {
                        runCatching { ApiClient.service.logout() }
                        UserSession.clear()
                        isLoggedIn.value = false
                        profile.value = null
                    }
                },
                statusMessage = statusMessage.value,
                onStatusChange = { statusMessage.value = it }
            )
        } else {
            ProfileLoggedOut(
                statusMessage = statusMessage.value,
                onStatusChange = { statusMessage.value = it },
                onLoginSuccess = { user ->
                    profile.value = user
                    isLoggedIn.value = true
                }
            )
        }
    }
}

@Composable
private fun ProfileLoggedOut(
    statusMessage: String?,
    onStatusChange: (String?) -> Unit,
    onLoginSuccess: (UserProfileDto) -> Unit
) {
    val tabIndex = remember { mutableStateOf(0) }
    val scope = rememberCoroutineScope()
    val context = LocalContext.current

    LazyColumn(
        modifier = Modifier
            .fillMaxSize()
            .padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp)
    ) {
        item {
            Card(
                modifier = Modifier.fillMaxWidth(),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
            ) {
                Column(
                    modifier = Modifier.padding(20.dp),
                    horizontalAlignment = Alignment.CenterHorizontally,
                    verticalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    Image(
                        painter = painterResource(id = R.drawable.ic_guidexy_compass_round),
                        contentDescription = null,
                        modifier = Modifier.size(72.dp)
                    )
                    Text(
                        "Hesabınıza giriş yapın",
                        style = MaterialTheme.typography.titleLarge,
                        textAlign = TextAlign.Center
                    )
                    Text(
                        "Yorum yazmak, fotoğraf paylaşmak ve profilinizi yönetmek için giriş yapın.",
                        style = MaterialTheme.typography.bodyMedium,
                        textAlign = TextAlign.Center
                    )
                }
            }
        }

        item {
            ScrollableTabRow(selectedTabIndex = tabIndex.value, edgePadding = 0.dp) {
                Tab(
                    selected = tabIndex.value == 0,
                    onClick = { tabIndex.value = 0 },
                    text = { Text("Giriş Yap") }
                )
                Tab(
                    selected = tabIndex.value == 1,
                    onClick = { tabIndex.value = 1 },
                    text = { Text("Üye Ol") }
                )
            }
        }

        item {
            if (tabIndex.value == 0) {
                AuthForm(
                    title = "Giriş Yap",
                    showNameField = false,
                    onSubmit = { name, email, password ->
                        scope.launch {
                            onStatusChange(null)
                            val response = runCatching {
                                ApiClient.service.login(AuthRequest(email, password))
                            }.getOrNull()
                            if (response?.success == true && response.user != null) {
                                UserSession.update(response.user)
                                onLoginSuccess(response.user)
                                Toast.makeText(context, "Giriş başarılı.", Toast.LENGTH_SHORT).show()
                            } else {
                                onStatusChange("Giriş başarısız.")
                                Toast.makeText(context, "Giriş başarısız.", Toast.LENGTH_SHORT).show()
                            }
                        }
                    }
                )
            } else {
                AuthForm(
                    title = "Üye Ol",
                    showNameField = true,
                    onSubmit = { name, email, password ->
                        scope.launch {
                            onStatusChange(null)
                            val response = runCatching {
                                ApiClient.service.register(RegisterRequest(name, email, password))
                            }.getOrNull()
                            if (response?.success == true && response.user != null) {
                                UserSession.update(response.user)
                                onLoginSuccess(response.user)
                                Toast.makeText(context, "Kayıt başarılı.", Toast.LENGTH_SHORT).show()
                            } else {
                                onStatusChange("Kayıt başarısız.")
                                Toast.makeText(context, "Kayıt başarısız.", Toast.LENGTH_SHORT).show()
                            }
                        }
                    }
                )
            }
        }
        statusMessage?.let { message ->
            item {
                Text(
                    message,
                    style = MaterialTheme.typography.labelMedium,
                    color = if (message.contains("başarısız", true)) Color(0xFFC62828) else Color(0xFF2E7D32)
                )
            }
        }
    }
}

@Composable
private fun AuthForm(
    title: String,
    showNameField: Boolean,
    onSubmit: (String, String, String) -> Unit
) {
    val name = remember { mutableStateOf("") }
    val email = remember { mutableStateOf("") }
    val password = remember { mutableStateOf("") }

    Card(
        modifier = Modifier.fillMaxWidth(),
        elevation = CardDefaults.cardElevation(1.dp)
    ) {
        Column(
            modifier = Modifier.padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Text(title, style = MaterialTheme.typography.titleMedium)
            if (showNameField) {
                TextField(
                    value = name.value,
                    onValueChange = { name.value = it },
                    modifier = Modifier.fillMaxWidth(),
                    label = { Text("Ad Soyad") }
                )
            }
            TextField(
                value = email.value,
                onValueChange = { email.value = it },
                modifier = Modifier.fillMaxWidth(),
                label = { Text("E-posta") }
            )
            TextField(
                value = password.value,
                onValueChange = { password.value = it },
                modifier = Modifier.fillMaxWidth(),
                label = { Text("Şifre") }
            )
            Button(
                onClick = { onSubmit(name.value, email.value, password.value) },
                modifier = Modifier.fillMaxWidth()
            ) {
                Text(title)
            }
            Text(
                "Devam ederek kullanım koşullarını kabul etmiş olursunuz.",
                style = MaterialTheme.typography.labelSmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
    }
}

@Composable
private fun ProfileLoggedIn(
    profile: UserProfileDto?,
    navController: NavController,
    onProfileUpdated: (UserProfileDto?) -> Unit,
    onLogout: () -> Unit,
    statusMessage: String?,
    onStatusChange: (String?) -> Unit
) {
    val scope = rememberCoroutineScope()
    val tabIndex = remember { mutableStateOf(0) }
    val reviewTabIndex = remember { mutableStateOf(0) }
    val userReviews = remember { mutableStateOf<List<UserReviewItemDto>>(emptyList()) }
    val reviewPage = remember { mutableStateOf(1) }
    val reviewTotalPages = remember { mutableStateOf(1) }
    val reviewAnsweredPage = remember { mutableStateOf(1) }
    val reviewUnansweredPage = remember { mutableStateOf(1) }
    val reviewAnsweredTotalPages = remember { mutableStateOf(1) }
    val reviewUnansweredTotalPages = remember { mutableStateOf(1) }
    val reviewAnsweredTotal = remember { mutableStateOf(0) }
    val reviewUnansweredTotal = remember { mutableStateOf(0) }
    val ownerPlaces = remember { mutableStateOf<List<OwnerPlaceDto>>(emptyList()) }
    val ownerPlacesPage = remember { mutableStateOf(1) }
    val ownerPlacesTotalPages = remember { mutableStateOf(1) }
    val selectedOwnerPlace = remember { mutableStateOf<OwnerPlaceDto?>(null) }
    val ownerReviewTabIndex = remember { mutableStateOf(0) }
    val ownerAnsweredReviews = remember { mutableStateOf<List<PlaceReviewDto>>(emptyList()) }
    val ownerUnansweredReviews = remember { mutableStateOf<List<PlaceReviewDto>>(emptyList()) }
    val ownerAnsweredPage = remember { mutableStateOf(1) }
    val ownerUnansweredPage = remember { mutableStateOf(1) }
    val ownerAnsweredTotalPages = remember { mutableStateOf(1) }
    val ownerUnansweredTotalPages = remember { mutableStateOf(1) }
    val ownerAnsweredTotal = remember { mutableStateOf(0) }
    val ownerUnansweredTotal = remember { mutableStateOf(0) }
    val ownerReviewRefreshKey = remember { mutableStateOf(0) }
    val ownerReplyDialog = remember { mutableStateOf<OwnerReviewReplyTarget?>(null) }
    val ownerReplyText = remember { mutableStateOf("") }
    val name = remember(profile) { mutableStateOf(profile?.name.orEmpty()) }
    val email = remember(profile) { mutableStateOf(profile?.email.orEmpty()) }
    val password = remember { mutableStateOf("") }
    val avatarUrl = remember(profile) {
        mutableStateOf(profile?.profile_photo ?: profile?.avatar_url)
    }
    val isBusinessOwner = profile?.role == "business_owner"
    val mainTabs = remember(isBusinessOwner) {
        if (isBusinessOwner) {
            listOf("Profil", "Profil Düzenle", "Yorumlarım", "İşletmelerim", "İşletme Yorumları")
        } else {
            listOf("Profil", "Profil Düzenle", "Yorumlarım")
        }
    }
    val ownerPlacesTabIndex = if (isBusinessOwner) 3 else -1
    val ownerReviewsTabIndex = if (isBusinessOwner) 4 else -1
    val context = androidx.compose.ui.platform.LocalContext.current
    val avatarPicker = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.GetContent()
    ) { uri ->
        if (uri != null) {
            scope.launch {
                val file = createTempUploadFile(context, uri)
                if (file != null) {
                    val requestBody = file.readBytes().toRequestBody("image/*".toMediaTypeOrNull())
                    val part = MultipartBody.Part.createFormData("file", file.name, requestBody)
                    val userId = profile?.id
                    val userIdBody = userId?.toString()?.toRequestBody("text/plain".toMediaTypeOrNull())
                    if (userIdBody == null) {
                        onStatusChange("Kullanıcı bilgisi bulunamadı.")
                        file.delete()
                        return@launch
                    }
                    val response = runCatching {
                        ApiClient.service.uploadAvatar(userIdBody, part)
                    }.getOrNull()
                    file.delete()
                    if (response?.url != null) {
                        avatarUrl.value = response.url
                        val updatedProfile = profile?.copy(profile_photo = response.url, avatar_url = response.url)
                        onProfileUpdated(updatedProfile)
                        UserSession.update(updatedProfile)
                        onStatusChange("Avatar güncellendi.")
                    } else {
                        onStatusChange("Avatar yüklenemedi.")
                    }
                } else {
                    onStatusChange("Avatar yüklenemedi.")
                }
            }
        }
    }

    LaunchedEffect(reviewAnsweredPage.value, profile?.id) {
        val userId = profile?.id ?: return@LaunchedEffect
        val response = runCatching {
            ApiClient.service.userReviews(userId, reviewAnsweredPage.value, replyStatus = "answered")
        }.getOrNull()
        if (response?.success == true) {
            if (reviewTabIndex.value == 0) {
                userReviews.value = response.reviews
            }
            reviewAnsweredTotalPages.value = response.total_pages
            reviewAnsweredTotal.value = response.total
        }
    }

    LaunchedEffect(reviewUnansweredPage.value, profile?.id) {
        val userId = profile?.id ?: return@LaunchedEffect
        val response = runCatching {
            ApiClient.service.userReviews(userId, reviewUnansweredPage.value, replyStatus = "unanswered")
        }.getOrNull()
        if (response?.success == true) {
            if (reviewTabIndex.value == 1) {
                userReviews.value = response.reviews
            }
            reviewUnansweredTotalPages.value = response.total_pages
            reviewUnansweredTotal.value = response.total
        }
    }

    LaunchedEffect(ownerPlacesPage.value, profile?.id, isBusinessOwner) {
        if (!isBusinessOwner) {
            ownerPlaces.value = emptyList()
            ownerPlacesTotalPages.value = 1
            selectedOwnerPlace.value = null
            return@LaunchedEffect
        }
        val userId = profile?.id ?: return@LaunchedEffect
        val response = runCatching {
            ApiClient.service.ownerPlaces(userId, ownerPlacesPage.value)
        }.getOrNull()
        if (response?.success == true) {
            ownerPlaces.value = response.places
            ownerPlacesTotalPages.value = response.total_pages
            val selectedId = selectedOwnerPlace.value?.id
            if (selectedId == null || response.places.none { it.id == selectedId }) {
                selectedOwnerPlace.value = response.places.firstOrNull()
            }
        }
    }

    LaunchedEffect(
        selectedOwnerPlace.value?.id,
        ownerAnsweredPage.value,
        ownerReviewRefreshKey.value,
        isBusinessOwner
    ) {
        if (!isBusinessOwner) {
            ownerAnsweredReviews.value = emptyList()
            ownerAnsweredTotalPages.value = 1
            return@LaunchedEffect
        }
        val placeId = selectedOwnerPlace.value?.id ?: return@LaunchedEffect
        val response = runCatching {
            ApiClient.service.placeReviews(placeId, ownerAnsweredPage.value, "new", "answered")
        }.getOrNull()
        if (response != null) {
            ownerAnsweredReviews.value = response.reviews
            ownerAnsweredTotalPages.value = response.total_pages
            ownerAnsweredTotal.value = response.total
        }
    }

    LaunchedEffect(
        selectedOwnerPlace.value?.id,
        ownerUnansweredPage.value,
        ownerReviewRefreshKey.value,
        isBusinessOwner
    ) {
        if (!isBusinessOwner) {
            ownerUnansweredReviews.value = emptyList()
            ownerUnansweredTotalPages.value = 1
            return@LaunchedEffect
        }
        val placeId = selectedOwnerPlace.value?.id ?: return@LaunchedEffect
        val response = runCatching {
            ApiClient.service.placeReviews(placeId, ownerUnansweredPage.value, "new", "unanswered")
        }.getOrNull()
        if (response != null) {
            ownerUnansweredReviews.value = response.reviews
            ownerUnansweredTotalPages.value = response.total_pages
            ownerUnansweredTotal.value = response.total
        }
    }

    LaunchedEffect(isBusinessOwner) {
        if (tabIndex.value > mainTabs.lastIndex) {
            tabIndex.value = 0
        }
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        ScrollableTabRow(selectedTabIndex = tabIndex.value, edgePadding = 0.dp) {
            mainTabs.forEachIndexed { index, label ->
                Tab(
                    selected = tabIndex.value == index,
                    onClick = { tabIndex.value = index },
                    text = { Text(label) }
                )
            }
        }

        when (tabIndex.value) {
            0 -> {
                LazyColumn(
                    modifier = Modifier.fillMaxSize(),
                    verticalArrangement = Arrangement.spacedBy(16.dp)
                ) {
                    item {
                        Card(
                            modifier = Modifier.fillMaxWidth(),
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
                        ) {
                            Column(modifier = Modifier.padding(16.dp)) {
                                Row(
                                    modifier = Modifier.fillMaxWidth(),
                                    horizontalArrangement = Arrangement.SpaceBetween,
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Text("Profilim", style = MaterialTheme.typography.titleLarge)
                                    TextButton(onClick = onLogout) { Text("Çıkış Yap") }
                                }
                                Spacer(modifier = Modifier.height(8.dp))
                                Row(verticalAlignment = Alignment.CenterVertically) {
                                    Card(
                                        modifier = Modifier.size(64.dp),
                                        elevation = CardDefaults.cardElevation(1.dp)
                                    ) {
                                        if (!avatarUrl.value.isNullOrBlank()) {
                                            Image(
                                                painter = rememberAsyncImagePainter(avatarUrl.value),
                                                contentDescription = null,
                                                contentScale = ContentScale.Crop,
                                                modifier = Modifier.fillMaxSize()
                                            )
                                        } else {
                                            Image(
                                                painter = painterResource(id = R.drawable.ic_guidexy_compass_round),
                                                contentDescription = null,
                                                modifier = Modifier.fillMaxSize()
                                            )
                                        }
                                    }
                                    Spacer(modifier = Modifier.width(12.dp))
                                    Column {
                                        Text(profile?.name ?: "İsim Soyisim", fontWeight = FontWeight.SemiBold)
                                        Text(
                                            profile?.email ?: "example@mail.com",
                                            style = MaterialTheme.typography.bodySmall
                                        )
                                    }
                                }
                                TextButton(onClick = { avatarPicker.launch("image/*") }) {
                                    Text("Profil Fotoğrafı Değiştir")
                                }
                            }
                        }
                    }
                }
            }
            1 -> {
                LazyColumn(
                    modifier = Modifier.fillMaxSize(),
                    verticalArrangement = Arrangement.spacedBy(16.dp)
                ) {
                    item {
                        Card(
                            modifier = Modifier.fillMaxWidth(),
                            elevation = CardDefaults.cardElevation(1.dp)
                        ) {
                            Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                                Text("Profil Düzenle", style = MaterialTheme.typography.titleMedium)
                                TextField(
                                    value = name.value,
                                    onValueChange = { name.value = it },
                                    label = { Text("Ad Soyad") }
                                )
                                TextField(
                                    value = email.value,
                                    onValueChange = { email.value = it },
                                    label = { Text("E-posta") }
                                )
                                TextField(
                                    value = password.value,
                                    onValueChange = { password.value = it },
                                    label = { Text("Yeni Şifre") }
                                )
                                Button(
                                    onClick = {
                                        scope.launch {
                                            val userId = profile?.id
                                            if (userId == null || userId == 0L) {
                                                onStatusChange("Kullanıcı bilgisi bulunamadı.")
                                                return@launch
                                            }
                                            val response = runCatching {
                                                ApiClient.service.updateProfile(
                                                    com.guidexy.app.data.ProfileUpdateRequest(
                                                        user_id = userId,
                                                        name = name.value,
                                                        email = email.value,
                                                        password = password.value.takeIf { it.isNotBlank() }
                                                    )
                                                )
                                            }.getOrNull()
                                            if (response?.success == true && response.user != null) {
                                                onProfileUpdated(response.user)
                                                UserSession.update(response.user)
                                                onStatusChange("Profil güncellendi.")
                                                password.value = ""
                                            } else {
                                                onStatusChange("Profil güncellenemedi.")
                                            }
                                        }
                                    },
                                    modifier = Modifier.fillMaxWidth()
                                ) {
                                    Text("Kaydet")
                                }
                                statusMessage?.let { message ->
                                    Text(
                                        message,
                                        style = MaterialTheme.typography.labelMedium,
                                        color = if (message.contains("güncellendi", true)) Color(0xFF2E7D32) else Color(0xFFC62828)
                                    )
                                }
                            }
                        }
                    }
                }
            }
            2 -> {
                val activeReviews = userReviews.value
                val activePage = if (reviewTabIndex.value == 0) reviewAnsweredPage.value else reviewUnansweredPage.value
                val activeTotalPages = if (reviewTabIndex.value == 0) {
                    reviewAnsweredTotalPages.value
                } else {
                    reviewUnansweredTotalPages.value
                }
                LazyColumn(
                    modifier = Modifier.fillMaxSize(),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    item {
                        Text("Yorumlarım", style = MaterialTheme.typography.titleMedium)
                        Spacer(modifier = Modifier.height(8.dp))
                        Card(
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)
                        ) {
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(12.dp),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                                    Text("Yanıtlanan", style = MaterialTheme.typography.labelSmall)
                                    Text(
                                        reviewAnsweredTotal.value.toString(),
                                        style = MaterialTheme.typography.titleMedium,
                                        fontWeight = FontWeight.Bold,
                                        color = MaterialTheme.colorScheme.primary
                                    )
                                }
                                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                                    Text("Yanıtlanmayan", style = MaterialTheme.typography.labelSmall)
                                    Text(
                                        reviewUnansweredTotal.value.toString(),
                                        style = MaterialTheme.typography.titleMedium,
                                        fontWeight = FontWeight.Bold,
                                        color = MaterialTheme.colorScheme.error
                                    )
                                }
                            }
                        }
                        Spacer(modifier = Modifier.height(8.dp))
                        ScrollableTabRow(selectedTabIndex = reviewTabIndex.value, edgePadding = 0.dp) {
                            Tab(
                                selected = reviewTabIndex.value == 0,
                                onClick = {
                                    reviewTabIndex.value = 0
                                    reviewAnsweredPage.value = 1
                                },
                                text = { Text("Yanıtlanan") }
                            )
                            Tab(
                                selected = reviewTabIndex.value == 1,
                                onClick = {
                                    reviewTabIndex.value = 1
                                    reviewUnansweredPage.value = 1
                                },
                                text = { Text("Yanıtlanmayan") }
                            )
                        }
                        Spacer(modifier = Modifier.height(8.dp))
                    }
                    if (activeReviews.isEmpty()) {
                        item {
                            Text(
                                "Bu sekmede yorum bulunamadı.",
                                style = MaterialTheme.typography.bodyMedium,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    }
                    items(activeReviews) { review ->
                        Card(
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable { navController.navigate("place/${review.place_id}") },
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)
                        ) {
                            Row(
                                modifier = Modifier.padding(12.dp),
                                horizontalArrangement = Arrangement.spacedBy(12.dp)
                            ) {
                                Card(
                                    modifier = Modifier.size(56.dp),
                                    elevation = CardDefaults.cardElevation(0.dp)
                                ) {
                                    val imageUrl = review.place_image
                                    if (!imageUrl.isNullOrBlank()) {
                                        Image(
                                            painter = rememberAsyncImagePainter(imageUrl),
                                            contentDescription = null,
                                            contentScale = ContentScale.Crop,
                                            modifier = Modifier.fillMaxSize()
                                        )
                                    } else {
                                        Image(
                                            painter = painterResource(id = R.drawable.ic_guidexy_compass_round),
                                            contentDescription = null,
                                            modifier = Modifier.fillMaxSize()
                                        )
                                    }
                                }
                                Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
                                    Text(
                                        review.place_name ?: "İşletme",
                                        style = MaterialTheme.typography.labelSmall,
                                        color = Color.Gray
                                    )
                                    Row(
                                        horizontalArrangement = Arrangement.spacedBy(2.dp),
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        val stars = review.rating.coerceIn(0, 5)
                                        repeat(5) { index ->
                                            Icon(
                                                imageVector = if (index < stars) Icons.Default.Star else Icons.Default.StarBorder,
                                                contentDescription = null,
                                                tint = MaterialTheme.colorScheme.primary,
                                                modifier = Modifier.size(14.dp)
                                            )
                                        }
                                    }
                                    Text(review.text ?: "", style = MaterialTheme.typography.bodyMedium)
                                    Text(
                                        formatProfileReviewDate(review.created_at) ?: "",
                                        style = MaterialTheme.typography.labelSmall,
                                        color = Color.Gray
                                    )
                                    review.reply?.reply_text?.takeIf { it.isNotBlank() }?.let { reply ->
                                        Card(
                                            colors = CardDefaults.cardColors(
                                                containerColor = MaterialTheme.colorScheme.surface
                                            )
                                        ) {
                                            Column(modifier = Modifier.padding(8.dp)) {
                                                Text(
                                                    "İşletme Yanıtı",
                                                    style = MaterialTheme.typography.labelSmall,
                                                    color = MaterialTheme.colorScheme.primary
                                                )
                                                val replyDate = formatProfileReviewDate(
                                                    review.reply?.updated_at ?: review.reply?.created_at
                                                )
                                                if (!replyDate.isNullOrBlank()) {
                                                    Text(
                                                        replyDate,
                                                        style = MaterialTheme.typography.labelSmall,
                                                        color = MaterialTheme.colorScheme.onSurfaceVariant
                                                    )
                                                }
                                                Text(reply, style = MaterialTheme.typography.bodySmall)
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    item {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            TextButton(
                                onClick = {
                                    if (reviewTabIndex.value == 0) {
                                        reviewAnsweredPage.value =
                                            (reviewAnsweredPage.value - 1).coerceAtLeast(1)
                                    } else {
                                        reviewUnansweredPage.value =
                                            (reviewUnansweredPage.value - 1).coerceAtLeast(1)
                                    }
                                },
                                enabled = activePage > 1
                            ) {
                                Text("Önceki")
                            }
                            Text("$activePage/$activeTotalPages", style = MaterialTheme.typography.labelMedium)
                            TextButton(
                                onClick = {
                                    if (reviewTabIndex.value == 0) {
                                        reviewAnsweredPage.value =
                                            (reviewAnsweredPage.value + 1).coerceAtMost(reviewAnsweredTotalPages.value)
                                    } else {
                                        reviewUnansweredPage.value =
                                            (reviewUnansweredPage.value + 1).coerceAtMost(reviewUnansweredTotalPages.value)
                                    }
                                },
                                enabled = activePage < activeTotalPages
                            ) {
                                Text("Sonraki")
                            }
                        }
                    }
                }
            }
            ownerPlacesTabIndex -> {
                LazyColumn(
                    modifier = Modifier.fillMaxSize(),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    item {
                        Text("İşletmelerim", style = MaterialTheme.typography.titleMedium)
                        Spacer(modifier = Modifier.height(8.dp))
                    }
                    if (ownerPlaces.value.isEmpty()) {
                        item {
                            Text(
                                "İşletme bulunamadı.",
                                style = MaterialTheme.typography.bodyMedium,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    }
                    items(ownerPlaces.value) { place ->
                        Card(
                            modifier = Modifier
                                .fillMaxWidth(),
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)
                        ) {
                            Row(
                                modifier = Modifier.padding(12.dp),
                                horizontalArrangement = Arrangement.spacedBy(12.dp)
                            ) {
                                Card(
                                    modifier = Modifier.size(56.dp),
                                    elevation = CardDefaults.cardElevation(0.dp)
                                ) {
                                    val imageUrl = place.business_image
                                    if (!imageUrl.isNullOrBlank()) {
                                        Image(
                                            painter = rememberAsyncImagePainter(imageUrl),
                                            contentDescription = null,
                                            contentScale = ContentScale.Crop,
                                            modifier = Modifier.fillMaxSize()
                                        )
                                    } else {
                                        Image(
                                            painter = painterResource(id = R.drawable.ic_guidexy_compass_round),
                                            contentDescription = null,
                                            modifier = Modifier.fillMaxSize()
                                        )
                                    }
                                }
                                Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
                                    Text(
                                        place.name,
                                        style = MaterialTheme.typography.titleSmall,
                                        fontWeight = FontWeight.SemiBold
                                    )
                                    place.formatted_address?.takeIf { it.isNotBlank() }?.let { address ->
                                        Text(
                                            address,
                                            style = MaterialTheme.typography.labelSmall,
                                            color = Color.Gray
                                        )
                                    }
                                    place.city_name?.takeIf { it.isNotBlank() }?.let { city ->
                                        Text(
                                            city,
                                            style = MaterialTheme.typography.labelSmall,
                                            color = Color.Gray
                                        )
                                    }
                                    val ratingValue = place.rating ?: 0.0
                                    Row(
                                        horizontalArrangement = Arrangement.spacedBy(2.dp),
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        val stars = ratingValue.toInt().coerceIn(0, 5)
                                        repeat(5) { index ->
                                            Icon(
                                                imageVector = if (index < stars) Icons.Default.Star else Icons.Default.StarBorder,
                                                contentDescription = null,
                                                tint = Color(0xFFFFC107),
                                                modifier = Modifier.size(14.dp)
                                            )
                                        }
                                        Text(
                                            String.format("%.1f", ratingValue),
                                            style = MaterialTheme.typography.labelSmall,
                                            fontWeight = FontWeight.Bold,
                                            color = MaterialTheme.colorScheme.onSurfaceVariant
                                        )
                                    }
                                    Spacer(modifier = Modifier.height(4.dp))
                                    Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                                        Button(
                                            onClick = { navController.navigate("place/${place.id}") }
                                        ) {
                                            Text("Detay")
                                        }
                                        Button(
                                            onClick = {
                                                selectedOwnerPlace.value = place
                                                ownerReviewTabIndex.value = 1
                                                ownerAnsweredPage.value = 1
                                                ownerUnansweredPage.value = 1
                                                tabIndex.value = ownerReviewsTabIndex
                                            }
                                        ) {
                                            Text("Yorumlar")
                                        }
                                    }
                                }
                            }
                        }
                    }
                    item {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            TextButton(
                                onClick = { ownerPlacesPage.value = (ownerPlacesPage.value - 1).coerceAtLeast(1) },
                                enabled = ownerPlacesPage.value > 1
                            ) {
                                Text("Önceki")
                            }
                            Text(
                                "${ownerPlacesPage.value}/${ownerPlacesTotalPages.value}",
                                style = MaterialTheme.typography.labelMedium
                            )
                            TextButton(
                                onClick = {
                                    ownerPlacesPage.value =
                                        (ownerPlacesPage.value + 1).coerceAtMost(ownerPlacesTotalPages.value)
                                },
                                enabled = ownerPlacesPage.value < ownerPlacesTotalPages.value
                            ) {
                                Text("Sonraki")
                            }
                        }
                    }
                }
            }
            ownerReviewsTabIndex -> {
                val activeReviews = if (ownerReviewTabIndex.value == 0) {
                    ownerAnsweredReviews.value
                } else {
                    ownerUnansweredReviews.value
                }
                val activePage = if (ownerReviewTabIndex.value == 0) {
                    ownerAnsweredPage.value
                } else {
                    ownerUnansweredPage.value
                }
                val activeTotalPages = if (ownerReviewTabIndex.value == 0) {
                    ownerAnsweredTotalPages.value
                } else {
                    ownerUnansweredTotalPages.value
                }
                LazyColumn(
                    modifier = Modifier.fillMaxSize(),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    item {
                        Text("İşletme Yorumları", style = MaterialTheme.typography.titleMedium)
                        selectedOwnerPlace.value?.let { place ->
                            Spacer(modifier = Modifier.height(6.dp))
                            Text(
                                place.name,
                                style = MaterialTheme.typography.bodyMedium,
                                fontWeight = FontWeight.SemiBold
                            )
                        }
                        Spacer(modifier = Modifier.height(8.dp))
                        Card(
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)
                        ) {
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(12.dp),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                                    Text("Yanıtlanan", style = MaterialTheme.typography.labelSmall)
                                    Text(
                                        ownerAnsweredTotal.value.toString(),
                                        style = MaterialTheme.typography.titleMedium,
                                        fontWeight = FontWeight.Bold,
                                        color = MaterialTheme.colorScheme.primary
                                    )
                                }
                                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                                    Text("Yanıtlanmayan", style = MaterialTheme.typography.labelSmall)
                                    Text(
                                        ownerUnansweredTotal.value.toString(),
                                        style = MaterialTheme.typography.titleMedium,
                                        fontWeight = FontWeight.Bold,
                                        color = MaterialTheme.colorScheme.error
                                    )
                                }
                            }
                        }
                        Spacer(modifier = Modifier.height(8.dp))
                        ScrollableTabRow(selectedTabIndex = ownerReviewTabIndex.value, edgePadding = 0.dp) {
                            Tab(
                                selected = ownerReviewTabIndex.value == 0,
                                onClick = {
                                    ownerReviewTabIndex.value = 0
                                    ownerAnsweredPage.value = 1
                                },
                                text = { Text("Yanıtlanan") }
                            )
                            Tab(
                                selected = ownerReviewTabIndex.value == 1,
                                onClick = {
                                    ownerReviewTabIndex.value = 1
                                    ownerUnansweredPage.value = 1
                                },
                                text = { Text("Yanıtlanmayan") }
                            )
                        }
                        Spacer(modifier = Modifier.height(8.dp))
                    }
                    if (selectedOwnerPlace.value == null) {
                        item {
                            Text(
                                "Önce işletme seçin.",
                                style = MaterialTheme.typography.bodyMedium,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    } else if (activeReviews.isEmpty()) {
                        item {
                            Text(
                                "Bu sekmede yorum bulunamadı.",
                                style = MaterialTheme.typography.bodyMedium,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    } else {
                        items(activeReviews) { review ->
                            OwnerReviewCard(
                                review = review,
                                showReplyButton = ownerReviewTabIndex.value == 1,
                                showEditActions = ownerReviewTabIndex.value == 0,
                                onReply = {
                                    ownerReplyText.value = ""
                                    ownerReplyDialog.value = OwnerReviewReplyTarget(
                                        placeId = selectedOwnerPlace.value?.id ?: 0L,
                                        review = review
                                    )
                                },
                                onEdit = {
                                    ownerReplyText.value = review.reply?.reply_text.orEmpty()
                                    ownerReplyDialog.value = OwnerReviewReplyTarget(
                                        placeId = selectedOwnerPlace.value?.id ?: 0L,
                                        review = review
                                    )
                                },
                                onDelete = {
                                    val currentUser = profile ?: return@OwnerReviewCard
                                    val placeId = selectedOwnerPlace.value?.id ?: return@OwnerReviewCard
                                    scope.launch {
                                        val response = runCatching {
                                            ApiClient.service.submitReviewReply(
                                                ReviewReplyRequest(
                                                    place_id = placeId,
                                                    source = review.source,
                                                    review_ref = review.review_ref,
                                                    reply_text = "",
                                                    user_id = currentUser.id,
                                                    user_role = currentUser.role
                                                )
                                            )
                                        }.getOrNull()
                                        if (response?.error == null) {
                                            Toast.makeText(context, "Yanıt silindi.", Toast.LENGTH_SHORT).show()
                                            ownerReviewRefreshKey.value += 1
                                        } else {
                                            Toast.makeText(context, "Yanıt silinemedi.", Toast.LENGTH_SHORT).show()
                                        }
                                    }
                                }
                            )
                        }
                    }
                    if (selectedOwnerPlace.value != null) {
                        item {
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                TextButton(
                                    onClick = {
                                        if (ownerReviewTabIndex.value == 0) {
                                            ownerAnsweredPage.value =
                                                (ownerAnsweredPage.value - 1).coerceAtLeast(1)
                                        } else {
                                            ownerUnansweredPage.value =
                                                (ownerUnansweredPage.value - 1).coerceAtLeast(1)
                                        }
                                    },
                                    enabled = activePage > 1
                                ) {
                                    Text("Önceki")
                                }
                                Text("$activePage/$activeTotalPages", style = MaterialTheme.typography.labelMedium)
                                TextButton(
                                    onClick = {
                                        if (ownerReviewTabIndex.value == 0) {
                                            ownerAnsweredPage.value =
                                                (ownerAnsweredPage.value + 1).coerceAtMost(ownerAnsweredTotalPages.value)
                                        } else {
                                            ownerUnansweredPage.value =
                                                (ownerUnansweredPage.value + 1).coerceAtMost(ownerUnansweredTotalPages.value)
                                        }
                                    },
                                    enabled = activePage < activeTotalPages
                                ) {
                                    Text("Sonraki")
                                }
                            }
                        }
                    }
                }
            }
            else -> Unit
        }
    }

    ownerReplyDialog.value?.let { target ->
        OwnerReviewReplyDialog(
            review = target.review,
            replyText = ownerReplyText.value,
            onReplyChange = { ownerReplyText.value = it },
            onDismiss = { ownerReplyDialog.value = null },
            onSubmit = {
                val currentUser = profile ?: return@OwnerReviewReplyDialog
                if (target.placeId == 0L) return@OwnerReviewReplyDialog
                scope.launch {
                    val response = runCatching {
                        ApiClient.service.submitReviewReply(
                            ReviewReplyRequest(
                                place_id = target.placeId,
                                source = target.review.source,
                                review_ref = target.review.review_ref,
                                reply_text = ownerReplyText.value,
                                user_id = currentUser.id,
                                user_role = currentUser.role
                            )
                        )
                    }.getOrNull()
                    if (response?.error == null) {
                        Toast.makeText(context, "Yanıt gönderildi.", Toast.LENGTH_SHORT).show()
                        ownerReplyDialog.value = null
                        ownerReplyText.value = ""
                        ownerAnsweredPage.value = 1
                        ownerUnansweredPage.value = 1
                        ownerReviewRefreshKey.value += 1
                    } else {
                        Toast.makeText(context, "Yanıt gönderilemedi.", Toast.LENGTH_SHORT).show()
                    }
                }
            }
        )
    }
}

private data class OwnerReviewReplyTarget(
    val placeId: Long,
    val review: PlaceReviewDto
)

@Composable
private fun OwnerReviewCard(
    review: PlaceReviewDto,
    showReplyButton: Boolean,
    showEditActions: Boolean,
    onReply: () -> Unit,
    onEdit: () -> Unit,
    onDelete: () -> Unit
) {
    val expanded = remember { mutableStateOf(false) }
    val textExtra = review.text_extra ?: emptyMap()
    val showToggle = (review.text?.length ?: 0) > 120 || textExtra.isNotEmpty()
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)
    ) {
        Column(
            modifier = Modifier.padding(12.dp),
            verticalArrangement = Arrangement.spacedBy(6.dp)
        ) {
            Row(
                horizontalArrangement = Arrangement.spacedBy(12.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                Card(
                    modifier = Modifier.size(44.dp),
                    elevation = CardDefaults.cardElevation(0.dp)
                ) {
                    val avatarUrl = review.profile_photo_url
                    if (!avatarUrl.isNullOrBlank()) {
                        Image(
                            painter = rememberAsyncImagePainter(avatarUrl),
                            contentDescription = null,
                            contentScale = ContentScale.Crop,
                            modifier = Modifier.fillMaxSize()
                        )
                    } else {
                        Image(
                            painter = painterResource(id = R.drawable.ic_guidexy_compass_round),
                            contentDescription = null,
                            modifier = Modifier.fillMaxSize()
                        )
                    }
                }
                Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
                    Text(
                        review.author_name ?: "Yorumcu",
                        style = MaterialTheme.typography.labelMedium,
                        fontWeight = FontWeight.SemiBold
                    )
                    Row(
                        horizontalArrangement = Arrangement.spacedBy(2.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        val stars = (review.rating ?: 0.0).toInt().coerceIn(0, 5)
                        repeat(5) { index ->
                            Icon(
                                imageVector = if (index < stars) Icons.Default.Star else Icons.Default.StarBorder,
                                contentDescription = null,
                                tint = MaterialTheme.colorScheme.primary,
                                modifier = Modifier.size(14.dp)
                            )
                        }
                    }
                }
            }
            review.text?.takeIf { it.isNotBlank() }?.let { text ->
                Text(
                    text,
                    style = MaterialTheme.typography.bodyMedium,
                    maxLines = if (expanded.value) Int.MAX_VALUE else 3,
                    overflow = androidx.compose.ui.text.style.TextOverflow.Ellipsis
                )
            }
            if (showToggle) {
                TextButton(onClick = { expanded.value = !expanded.value }) {
                    Text(if (expanded.value) "Kısalt" else "Tümünü Gör")
                }
            }
            if (textExtra.isNotEmpty() && expanded.value) {
                Column(verticalArrangement = Arrangement.spacedBy(6.dp)) {
                    textExtra.forEach { (key, value) ->
                        Card(
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
                        ) {
                            Column(modifier = Modifier.padding(8.dp)) {
                                Text(
                                    key,
                                    style = MaterialTheme.typography.labelSmall,
                                    color = MaterialTheme.colorScheme.primary
                                )
                                Text(
                                    value,
                                    style = MaterialTheme.typography.bodySmall,
                                    fontWeight = FontWeight.Medium
                                )
                            }
                        }
                    }
                }
            }
            val dateText = formatProfileReviewDate(review.relative_time) ?: review.relative_time.orEmpty()
            if (dateText.isNotBlank()) {
                Text(
                    dateText,
                    style = MaterialTheme.typography.labelSmall,
                    color = Color.Gray
                )
            }
            review.reply?.reply_text?.takeIf { it.isNotBlank() }?.let { reply ->
                Card(
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
                ) {
                    Column(modifier = Modifier.padding(8.dp)) {
                        Text(
                            "Yanıt",
                            style = MaterialTheme.typography.labelSmall,
                            color = MaterialTheme.colorScheme.primary
                        )
                        val replyDate = formatProfileReviewDate(review.reply?.updated_at ?: review.reply?.created_at)
                        if (!replyDate.isNullOrBlank()) {
                            Text(
                                replyDate,
                                style = MaterialTheme.typography.labelSmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                        Text(reply, style = MaterialTheme.typography.bodySmall)
                    }
                }
            }
            if (showEditActions) {
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    OutlinedButton(onClick = onEdit) { Text("Düzenle") }
                    OutlinedButton(
                        onClick = onDelete,
                        colors = ButtonDefaults.outlinedButtonColors(
                            contentColor = MaterialTheme.colorScheme.error
                        )
                    ) {
                        Text("Sil")
                    }
                }
            }
            if (showReplyButton) {
                TextButton(onClick = onReply) {
                    Text("Yanıtla")
                }
            }
        }
    }
}

@Composable
private fun OwnerReviewReplyDialog(
    review: PlaceReviewDto,
    replyText: String,
    onReplyChange: (String) -> Unit,
    onDismiss: () -> Unit,
    onSubmit: () -> Unit
) {
    androidx.compose.ui.window.Dialog(onDismissRequest = onDismiss) {
        Card(
            modifier = Modifier
                .fillMaxWidth()
                .padding(12.dp),
            shape = MaterialTheme.shapes.large
        ) {
            Column(
                modifier = Modifier.padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                Text("Yanıtla", style = MaterialTheme.typography.titleMedium)
                Text(
                    review.text ?: "",
                    style = MaterialTheme.typography.bodySmall,
                    maxLines = 4,
                    overflow = androidx.compose.ui.text.style.TextOverflow.Ellipsis
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
                    TextButton(onClick = onSubmit) { Text("Gönder") }
                }
            }
        }
    }
}

private fun formatProfileReviewDate(value: String?): String? {
    if (value.isNullOrBlank()) return null
    val inputFormats = listOf(
        "yyyy-MM-dd HH:mm:ss",
        "yyyy-MM-dd'T'HH:mm:ss",
        "yyyy-MM-dd'T'HH:mm:ss.SSS'Z'"
    )
    val outputFormat = SimpleDateFormat("dd.MM.yyyy HH:mm", Locale.getDefault())
    inputFormats.forEach { pattern ->
        val parser = SimpleDateFormat(pattern, Locale.getDefault())
        val date = runCatching { parser.parse(value) }.getOrNull()
        if (date != null) {
            return outputFormat.format(date)
        }
    }
    return value
}

private fun createTempUploadFile(context: android.content.Context, uri: android.net.Uri): File? {
    return runCatching {
        val input = context.contentResolver.openInputStream(uri) ?: return@runCatching null
        val tempFile = File.createTempFile("avatar_", ".tmp", context.cacheDir)
        FileOutputStream(tempFile).use { out ->
            input.copyTo(out)
        }
        input.close()
        tempFile
    }.getOrNull()
}
