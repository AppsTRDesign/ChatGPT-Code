package com.guidexy.app.ui.components

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.List
import androidx.compose.material.icons.filled.Person
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable

@Composable
fun BottomNavBar(currentRoute: String?, onNavigate: (String) -> Unit) {
    NavigationBar {
        NavigationBarItem(
            selected = currentRoute == "home",
            onClick = { onNavigate("home") },
            icon = { androidx.compose.material3.Icon(Icons.Default.Home, contentDescription = null) },
            label = { Text("Keşfet") }
        )
        NavigationBarItem(
            selected = currentRoute == "categories",
            onClick = { onNavigate("categories") },
            icon = { androidx.compose.material3.Icon(Icons.Default.List, contentDescription = null) },
            label = { Text("Kategoriler") }
        )
        NavigationBarItem(
            selected = currentRoute == "profile",
            onClick = { onNavigate("profile") },
            icon = { androidx.compose.material3.Icon(Icons.Default.Person, contentDescription = null) },
            label = { Text("Hesabım") }
        )
    }
}
