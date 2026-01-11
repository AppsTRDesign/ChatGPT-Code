package com.guidexy.app.ui.components

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.filled.StarBorder
import androidx.compose.material.icons.filled.Visibility
import androidx.compose.material3.Card
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.unit.dp
import coil.compose.rememberAsyncImagePainter
import com.guidexy.app.data.PlaceDto
import java.text.DecimalFormat

@Composable
fun PlaceCard(place: PlaceDto, onClick: () -> Unit) {
    val distanceFormatter = remember { DecimalFormat("0.0") }
    Card(modifier = Modifier.fillMaxWidth().clickable { onClick() }) {
        Row(modifier = Modifier.padding(12.dp)) {
            val imageUrl = place.business_image
            if (!imageUrl.isNullOrBlank()) {
                androidx.compose.foundation.Image(
                    painter = rememberAsyncImagePainter(imageUrl),
                    contentDescription = place.name,
                    modifier = Modifier.width(80.dp).height(80.dp),
                    contentScale = ContentScale.Crop
                )
            } else {
                Card(modifier = Modifier.width(80.dp).height(80.dp)) {
                    Box(modifier = Modifier.padding(8.dp)) {
                        Text(place.name.take(1).uppercase())
                    }
                }
            }
            Spacer(modifier = Modifier.width(12.dp))
            Column(modifier = Modifier.weight(1f)) {
                Text(place.name, style = MaterialTheme.typography.titleMedium)
                Text(place.formatted_address ?: "", style = MaterialTheme.typography.bodySmall)
                RatingRow(rating = place.rating ?: place.combined_rating)
                Text(place.business_type ?: "", style = MaterialTheme.typography.bodySmall)
                Row(horizontalArrangement = Arrangement.spacedBy(6.dp), verticalAlignment = Alignment.CenterVertically) {
                    Icon(Icons.Default.Visibility, contentDescription = null, modifier = Modifier.width(16.dp).height(16.dp))
                    Text("Görüntülenme: ${place.views ?: 0}", style = MaterialTheme.typography.bodySmall)
                }
                place.distance_m?.let { distance ->
                    val km = distance / 1000.0
                    Row(horizontalArrangement = Arrangement.spacedBy(6.dp), verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.LocationOn, contentDescription = null, modifier = Modifier.width(16.dp).height(16.dp))
                        Text(
                            "Uzaklık: ${distanceFormatter.format(km)} km",
                            style = MaterialTheme.typography.bodySmall
                        )
                    }
                }
            }
        }
    }
}

@Composable
fun GridPlaceCard(place: PlaceDto, onClick: () -> Unit) {
    Card(modifier = Modifier.clickable { onClick() }) {
        Column {
            val imageUrl = place.business_image
            if (!imageUrl.isNullOrBlank()) {
                androidx.compose.foundation.Image(
                    painter = rememberAsyncImagePainter(imageUrl),
                    contentDescription = place.name,
                    modifier = Modifier.fillMaxWidth().height(140.dp),
                    contentScale = ContentScale.Crop
                )
            }
            Column(modifier = Modifier.padding(12.dp)) {
                Text(place.name, style = MaterialTheme.typography.titleSmall)
                Text(place.business_type ?: "", style = MaterialTheme.typography.labelSmall)
                RatingRow(rating = place.rating ?: place.combined_rating)
                Row(horizontalArrangement = Arrangement.spacedBy(6.dp), verticalAlignment = Alignment.CenterVertically) {
                    Icon(Icons.Default.Visibility, contentDescription = null, modifier = Modifier.width(14.dp).height(14.dp))
                    Text("Görüntülenme: ${place.views ?: 0}", style = MaterialTheme.typography.bodySmall)
                }
                place.distance_m?.let { distance ->
                    val km = distance / 1000.0
                    Row(horizontalArrangement = Arrangement.spacedBy(6.dp), verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.LocationOn, contentDescription = null, modifier = Modifier.width(14.dp).height(14.dp))
                        Text(
                            "Uzaklık: ${distanceFormatter.format(km)} km",
                            style = MaterialTheme.typography.bodySmall
                        )
                    }
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
        Text(label, style = MaterialTheme.typography.bodySmall)
    }
}
