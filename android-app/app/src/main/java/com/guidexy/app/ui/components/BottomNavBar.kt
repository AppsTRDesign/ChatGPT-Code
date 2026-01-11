package com.guidexy.app.ui.components

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.List
import androidx.compose.material.icons.filled.LocationCity
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Search
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.rememberScrollState
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp

@Composable
fun BottomNavBar(currentRoute: String?, onNavigate: (String) -> Unit) {
    NavigationBar {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .horizontalScroll(rememberScrollState()),
            horizontalArrangement = Arrangement.spacedBy(6.dp)
        ) {
            NavigationBarItem(
                selected = currentRoute?.startsWith("home") == true,
                onClick = { onNavigate("home") },
                icon = { androidx.compose.material3.Icon(Icons.Default.Home, contentDescription = null) },
                label = { Text("Keşfet") }
            )
            NavigationBarItem(
                selected = currentRoute?.startsWith("search") == true,
                onClick = { onNavigate("search") },
                icon = { androidx.compose.material3.Icon(Icons.Default.Search, contentDescription = null) },
                label = { Text("Ara") }
            )
            NavigationBarItem(
                selected = currentRoute?.startsWith("categories") == true,
                onClick = { onNavigate("categories") },
                icon = { androidx.compose.material3.Icon(Icons.Default.List, contentDescription = null) },
                label = { Text("Kategoriler") }
            )
            NavigationBarItem(
                selected = currentRoute?.startsWith("cities") == true || currentRoute?.startsWith("city_categories") == true,
                onClick = { onNavigate("cities") },
                icon = { androidx.compose.material3.Icon(Icons.Default.LocationCity, contentDescription = null) },
                label = { Text("Şehirler") }
            )
            NavigationBarItem(
                selected = currentRoute?.startsWith("profile") == true,
                onClick = { onNavigate("profile") },
                icon = { androidx.compose.material3.Icon(Icons.Default.Person, contentDescription = null) },
                label = { Text("Hesabım") }
            )
        }
    }
}
