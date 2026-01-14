package com.guidexy.app.ui.components

import androidx.compose.animation.animateColorAsState
import androidx.compose.animation.core.Spring
import androidx.compose.animation.core.animateDpAsState
import androidx.compose.animation.core.spring
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.GridView
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.LocationCity
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

// Önceki ekranla uyumlu Premium Renkler
private val PremiumPrimary = Color(0xFF6366F1)
private val PremiumSurface = Color(0xFFFFFFFF)

@Composable
fun BottomNavBar(currentRoute: String?, onNavigate: (String) -> Unit) {
    // Navigasyon öğelerini bir liste olarak tanımlayalım
    val items = listOf(
        NavigationItem("home", "Keşfet", Icons.Default.Home),
        NavigationItem("search", "Ara", Icons.Default.Search),
        NavigationItem("categories", "Kategori", Icons.Default.GridView),
        NavigationItem("cities", "Şehirler", Icons.Default.LocationCity),
        NavigationItem("profile", "Profil", Icons.Default.Person)
    )

    // Yüzen (Floating) Modern Bar Tasarımı
    Box(
        modifier = Modifier
            .fillMaxWidth()
            .navigationBarsPadding()
            .padding(horizontal = 24.dp, vertical = 16.dp)
    ) {
        Surface(
            modifier = Modifier
                .fillMaxWidth()
                .height(72.dp)
                .shadow(
                    elevation = 20.dp,
                    shape = RoundedCornerShape(24.dp),
                    spotColor = PremiumPrimary.copy(alpha = 0.3f)
                ),
            shape = RoundedCornerShape(24.dp),
            color = PremiumSurface.copy(alpha = 0.95f),
            tonalElevation = 8.dp
        ) {
            Row(
                modifier = Modifier.fillMaxSize(),
                horizontalArrangement = Arrangement.SpaceEvenly,
                verticalAlignment = Alignment.CenterVertically
            ) {
                items.forEach { item ->
                    val isSelected = when (item.route) {
                        "home" -> currentRoute?.startsWith("home") == true
                        "search" -> currentRoute?.startsWith("search") == true
                        "categories" -> currentRoute?.startsWith("categories") == true
                        "cities" -> currentRoute?.startsWith("cities") == true ||
                            currentRoute?.startsWith("city_categories") == true
                        "profile" -> currentRoute?.startsWith("profile") == true
                        else -> false
                    }

                    NavBarItem(
                        item = item,
                        isSelected = isSelected,
                        onClick = { onNavigate(item.route) }
                    )
                }
            }
        }
    }
}

@Composable
private fun NavBarItem(
    item: NavigationItem,
    isSelected: Boolean,
    onClick: () -> Unit
) {
    // Animasyon değerleri
    val animatedWeight by animateDpAsState(
        targetValue = if (isSelected) 36.dp else 0.dp,
        animationSpec = spring(stiffness = Spring.StiffnessLow),
        label = "width"
    )

    val contentColor by animateColorAsState(
        targetValue = if (isSelected) PremiumPrimary else Color.Gray.copy(alpha = 0.6f),
        label = "color"
    )

    Column(
        modifier = Modifier
            .clip(CircleShape)
            .clickable(
                interactionSource = remember { MutableInteractionSource() },
                indication = null,
                onClick = onClick
            )
            .padding(vertical = 8.dp, horizontal = 12.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Icon(
            imageVector = item.icon,
            contentDescription = item.title,
            tint = contentColor,
            modifier = Modifier.size(if (isSelected) 26.dp else 24.dp)
        )

        if (isSelected) {
            Text(
                text = item.title,
                style = MaterialTheme.typography.labelSmall.copy(
                    fontWeight = FontWeight.Bold,
                    fontSize = 10.sp
                ),
                color = PremiumPrimary,
                modifier = Modifier.padding(top = 4.dp)
            )
            Box(
                modifier = Modifier
                    .padding(top = 2.dp)
                    .size(4.dp)
                    .clip(CircleShape)
                    .background(PremiumPrimary)
            )
        }
    }
}

data class NavigationItem(
    val route: String,
    val title: String,
    val icon: ImageVector
)
