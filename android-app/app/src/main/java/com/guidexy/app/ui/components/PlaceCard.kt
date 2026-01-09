package com.guidexy.app.ui.components

import androidx.compose.foundation.Image
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.material3.Card
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.remember
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
            Image(
                painter = rememberAsyncImagePainter(place.business_image),
                contentDescription = place.name,
                modifier = Modifier.width(80.dp).height(80.dp),
                contentScale = ContentScale.Crop
            )
            Spacer(modifier = Modifier.width(12.dp))
            Column(modifier = Modifier.weight(1f)) {
                Text(place.name, style = MaterialTheme.typography.titleMedium)
                Text(place.formatted_address ?: "", style = MaterialTheme.typography.bodySmall)
                Text("Puan: ${place.rating ?: place.combined_rating ?: "-"}", style = MaterialTheme.typography.bodySmall)
                Text(place.business_type ?: "", style = MaterialTheme.typography.bodySmall)
                place.distance_m?.let { distance ->
                    val km = distance / 1000.0
                    Text(
                        "Uzaklık: ${distanceFormatter.format(km)} km",
                        style = MaterialTheme.typography.bodySmall
                    )
                }
            }
        }
    }
}
