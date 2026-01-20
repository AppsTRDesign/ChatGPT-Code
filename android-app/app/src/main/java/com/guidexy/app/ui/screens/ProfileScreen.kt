package com.guidexy.app.ui.screens

import androidx.compose.foundation.Image
import androidx.compose.foundation.background
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
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Tab
import androidx.compose.material3.TabRow
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
import com.guidexy.app.data.RegisterRequest
import com.guidexy.app.data.UserProfileDto
import com.guidexy.app.data.UserReviewItemDto
import com.guidexy.app.data.UserSession
import kotlinx.coroutines.launch
import androidx.compose.runtime.rememberCoroutineScope
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.ui.layout.ContentScale
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.toRequestBody
import java.io.File
import java.io.FileOutputStream
import java.text.SimpleDateFormat
import java.util.Locale

@Composable
fun ProfileScreen() {
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
            TabRow(selectedTabIndex = tabIndex.value) {
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
                            } else {
                                onStatusChange("Giriş başarısız.")
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
                            } else {
                                onStatusChange("Kayıt başarısız.")
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
    onProfileUpdated: (UserProfileDto?) -> Unit,
    onLogout: () -> Unit,
    statusMessage: String?,
    onStatusChange: (String?) -> Unit
) {
    val scope = rememberCoroutineScope()
    val tabIndex = remember { mutableStateOf(0) }
    val userReviews = remember { mutableStateOf<List<UserReviewItemDto>>(emptyList()) }
    val reviewPage = remember { mutableStateOf(1) }
    val reviewTotalPages = remember { mutableStateOf(1) }
    val name = remember(profile) { mutableStateOf(profile?.name.orEmpty()) }
    val email = remember(profile) { mutableStateOf(profile?.email.orEmpty()) }
    val password = remember { mutableStateOf("") }
    val avatarUrl = remember(profile) {
        mutableStateOf(profile?.profile_photo ?: profile?.avatar_url)
    }
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

    LaunchedEffect(reviewPage.value, profile?.id) {
        val userId = profile?.id ?: return@LaunchedEffect
        val response = runCatching { ApiClient.service.userReviews(userId, reviewPage.value) }.getOrNull()
        if (response?.success == true) {
            userReviews.value = response.reviews
            reviewTotalPages.value = response.total_pages
        }
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        TabRow(selectedTabIndex = tabIndex.value) {
            Tab(
                selected = tabIndex.value == 0,
                onClick = { tabIndex.value = 0 },
                text = { Text("Profil") }
            )
            Tab(
                selected = tabIndex.value == 1,
                onClick = { tabIndex.value = 1 },
                text = { Text("Profil Düzenle") }
            )
            Tab(
                selected = tabIndex.value == 2,
                onClick = { tabIndex.value = 2 },
                text = { Text("Yorumlarım") }
            )
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
            else -> {
                LazyColumn(
                    modifier = Modifier.fillMaxSize(),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    item {
                        Text("Yorumlarım", style = MaterialTheme.typography.titleMedium)
                    }
                    items(userReviews.value) { review ->
                        Card(
                            modifier = Modifier.fillMaxWidth(),
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
                                    Text(review.text ?: "", style = MaterialTheme.typography.bodyMedium)
                                    Text(
                                        formatProfileReviewDate(review.created_at) ?: "",
                                        style = MaterialTheme.typography.labelSmall,
                                        color = Color.Gray
                                    )
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
                                onClick = { reviewPage.value = (reviewPage.value - 1).coerceAtLeast(1) },
                                enabled = reviewPage.value > 1
                            ) {
                                Text("Önceki")
                            }
                            Text("${reviewPage.value}/${reviewTotalPages.value}", style = MaterialTheme.typography.labelMedium)
                            TextButton(
                                onClick = { reviewPage.value = (reviewPage.value + 1).coerceAtMost(reviewTotalPages.value) },
                                enabled = reviewPage.value < reviewTotalPages.value
                            ) {
                                Text("Sonraki")
                            }
                        }
                    }
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
