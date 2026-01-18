package com.guidexy.app.ui

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color

private val colorScheme = lightColorScheme(
    primary = Color(0xFF1E88E5),
    secondary = Color(0xFF1565C0),
    tertiary = Color(0xFF26A69A)
)

@Composable
fun GuideXYTheme(content: @Composable () -> Unit) {
    MaterialTheme(
        colorScheme = colorScheme,
        content = content
    )
}
