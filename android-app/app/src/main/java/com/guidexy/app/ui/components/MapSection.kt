package com.guidexy.app.ui.components

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.material3.Card
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import com.guidexy.app.data.PlaceDto
import org.osmdroid.config.Configuration
import org.osmdroid.tileprovider.tilesource.TileSourceFactory
import org.osmdroid.util.GeoPoint
import org.osmdroid.views.MapView
import org.osmdroid.views.overlay.Marker

@Composable
fun MapSection(
    places: List<PlaceDto>,
    onMarkerSelected: (PlaceDto) -> Unit,
    height: Dp
) {
    val context = LocalContext.current
    DisposableEffect(Unit) {
        Configuration.getInstance().userAgentValue = context.packageName
        onDispose { }
    }
    androidx.compose.ui.viewinterop.AndroidView(
        modifier = Modifier.fillMaxWidth().height(height),
        factory = {
            MapView(it).apply {
                setTileSource(TileSourceFactory.MAPNIK)
                setMultiTouchControls(true)
            }
        },
        update = { mapView ->
            mapView.overlays.removeAll { it is Marker }
            val placeKey = places.joinToString(separator = ",") { it.id.toString() }
            if (mapView.tag != placeKey) {
                val first = places.firstOrNull()
                val center = first?.latitude?.toDoubleOrNull()?.let { lat ->
                    val lng = first.longitude?.toDoubleOrNull() ?: return@let null
                    GeoPoint(lat, lng)
                } ?: GeoPoint(41.015137, 28.97953)
                mapView.controller.setZoom(12.0)
                mapView.controller.setCenter(center)
                mapView.tag = placeKey
            }
            places.forEach { place ->
                val lat = place.latitude?.toDoubleOrNull() ?: return@forEach
                val lng = place.longitude?.toDoubleOrNull() ?: return@forEach
                val marker = Marker(mapView).apply {
                    position = GeoPoint(lat, lng)
                    title = place.name
                    setAnchor(Marker.ANCHOR_CENTER, Marker.ANCHOR_BOTTOM)
                    setOnMarkerClickListener { _, _ ->
                        onMarkerSelected(place)
                        true
                    }
                }
                mapView.overlays.add(marker)
            }
            mapView.invalidate()
        }
    )
}

@Composable
fun MapPlaceCard(place: PlaceDto, onClick: () -> Unit) {
    Card(
        modifier = Modifier
            .width(220.dp)
            .clickable { onClick() }
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            Text(place.name, style = MaterialTheme.typography.titleSmall)
            Text(place.formatted_address ?: "", style = MaterialTheme.typography.bodySmall, maxLines = 2)
            Text("Puan: ${place.rating ?: place.combined_rating ?: "-"}", style = MaterialTheme.typography.labelSmall)
        }
    }
}
