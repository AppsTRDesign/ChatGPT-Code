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
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import com.guidexy.app.R

@Composable
fun ProfileScreen() {
    val isLoggedIn = remember { mutableStateOf(false) }

    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
    ) {
        if (isLoggedIn.value) {
            ProfileLoggedIn()
        } else {
            ProfileLoggedOut()
        }
    }
}

@Composable
private fun ProfileLoggedOut() {
    val tabIndex = remember { mutableStateOf(0) }

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
                AuthForm(title = "Giriş Yap", showNameField = false)
            } else {
                AuthForm(title = "Üye Ol", showNameField = true)
            }
        }
    }
}

@Composable
private fun AuthForm(title: String, showNameField: Boolean) {
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
            Button(onClick = { /* API ile giriş/üyelik */ }, modifier = Modifier.fillMaxWidth()) {
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
private fun ProfileLoggedIn() {
    val reviews = remember { listOf("Harika bir deneyimdi.", "Fiyat-performans çok iyiydi.") }

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
                Column(modifier = Modifier.padding(16.dp)) {
                    Text("Profilim", style = MaterialTheme.typography.titleLarge)
                    Spacer(modifier = Modifier.height(8.dp))
                    Text("İsim Soyisim", fontWeight = FontWeight.SemiBold)
                    Text("example@mail.com", style = MaterialTheme.typography.bodySmall)
                }
            }
        }

        item {
            Card(
                modifier = Modifier.fillMaxWidth(),
                elevation = CardDefaults.cardElevation(1.dp)
            ) {
                Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    Text("Profil Düzenle", style = MaterialTheme.typography.titleMedium)
                    TextField(value = "İsim Soyisim", onValueChange = {}, label = { Text("Ad Soyad") })
                    TextField(value = "example@mail.com", onValueChange = {}, label = { Text("E-posta") })
                    TextField(value = "", onValueChange = {}, label = { Text("Yeni Şifre") })
                    Button(onClick = { /* Profil güncelle */ }, modifier = Modifier.fillMaxWidth()) {
                        Text("Kaydet")
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
                Text("Yorumlarım", style = MaterialTheme.typography.titleMedium)
                TextButton(onClick = { /* Tümünü gör */ }) {
                    Text("Tümü")
                }
            }
        }

        items(reviews) { review ->
            Card(
                modifier = Modifier.fillMaxWidth(),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)
            ) {
                Column(modifier = Modifier.padding(12.dp)) {
                    Text(review, style = MaterialTheme.typography.bodyMedium)
                    Text("18.01.2025 15:15", style = MaterialTheme.typography.labelSmall, color = Color.Gray)
                }
            }
        }
    }
}
