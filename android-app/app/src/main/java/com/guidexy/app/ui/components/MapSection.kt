package com.guidexy.app.ui.components

import android.content.Context
import android.graphics.Bitmap
import android.graphics.Canvas
import android.graphics.Paint
import android.graphics.PorterDuff
import android.graphics.PorterDuffXfermode
import android.graphics.RectF
import android.graphics.drawable.GradientDrawable
import androidx.core.content.ContextCompat
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
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import coil.ImageLoader
import coil.request.ImageRequest
import coil.request.SuccessResult
import com.guidexy.app.R
import com.guidexy.app.data.PlaceDto
import org.osmdroid.bonuspack.clustering.MarkerClusterer
import org.osmdroid.bonuspack.clustering.RadiusMarkerClusterer
import org.osmdroid.bonuspack.clustering.StaticCluster
import org.osmdroid.config.Configuration
import org.osmdroid.tileprovider.tilesource.TileSourceFactory
import org.osmdroid.util.BoundingBox
import org.osmdroid.util.GeoPoint
import org.osmdroid.views.MapView
import org.osmdroid.views.overlay.Marker
import kotlinx.coroutines.launch

@Composable
fun MapSection(
    places: List<PlaceDto>,
    onMarkerSelected: (PlaceDto) -> Unit,
    height: Dp
) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    val imageLoader = remember { ImageLoader(context) }
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
            val placeKey = places.joinToString(separator = ",") { it.id.toString() }
            if (mapView.tag == placeKey) {
                return@AndroidView
            }
            mapView.tag = placeKey
            mapView.overlays.removeAll { it is Marker || it is MarkerClusterer }
            val points = places.mapNotNull { place ->
                val lat = place.latitude?.toDoubleOrNull() ?: return@mapNotNull null
                val lng = place.longitude?.toDoubleOrNull() ?: return@mapNotNull null
                GeoPoint(lat, lng)
            }
            if (points.isNotEmpty()) {
                val lats = points.map { it.latitude }
                val lngs = points.map { it.longitude }
                val box = BoundingBox(
                    lats.maxOrNull() ?: 0.0,
                    lngs.maxOrNull() ?: 0.0,
                    lats.minOrNull() ?: 0.0,
                    lngs.minOrNull() ?: 0.0
                )
                mapView.zoomToBoundingBox(box, true, 80)
            } else {
                val center = GeoPoint(41.015137, 28.97953)
                mapView.controller.setZoom(12.0)
                mapView.controller.setCenter(center)
            }
            val markerIcon = ContextCompat.getDrawable(context, R.drawable.ic_map_pin)
            val markerBaseBitmap = markerIcon?.let { drawableToBitmap(it) }
            val clusterer = ColorRadiusMarkerClusterer(context) { size ->
                val color = clusterColor(size)
                createClusterBitmap(context, color)
            }.apply {
                setRadius(90)
            }
            places.forEachIndexed { index, place ->
                val lat = place.latitude?.toDoubleOrNull() ?: return@forEachIndexed
                val lng = place.longitude?.toDoubleOrNull() ?: return@forEachIndexed
                val marker = Marker(mapView).apply {
                    position = GeoPoint(lat, lng)
                    title = place.name
                    setAnchor(Marker.ANCHOR_CENTER, Marker.ANCHOR_BOTTOM)
                    markerIcon?.let { icon = it }
                    setOnMarkerClickListener { _, _ ->
                        onMarkerSelected(place)
                        true
                    }
                }
                val imageUrl = place.business_image
                if (index < 40 && !imageUrl.isNullOrBlank() && markerBaseBitmap != null) {
                    scope.launch {
                        val thumb = loadThumbnailBitmap(context, imageLoader, imageUrl)
                        if (thumb != null) {
                            val composite = createPinThumbnailBitmap(markerBaseBitmap, thumb)
                            marker.icon = android.graphics.drawable.BitmapDrawable(context.resources, composite)
                            mapView.invalidate()
                        }
                    }
                }
                clusterer.add(marker)
            }
            mapView.overlays.add(clusterer)
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

private fun drawableToBitmap(drawable: android.graphics.drawable.Drawable): Bitmap {
    if (drawable is android.graphics.drawable.BitmapDrawable) {
        return drawable.bitmap
    }
    val bitmap = Bitmap.createBitmap(
        drawable.intrinsicWidth.coerceAtLeast(1),
        drawable.intrinsicHeight.coerceAtLeast(1),
        Bitmap.Config.ARGB_8888
    )
    val canvas = Canvas(bitmap)
    drawable.setBounds(0, 0, canvas.width, canvas.height)
    drawable.draw(canvas)
    return bitmap
}

private class ColorRadiusMarkerClusterer(
    context: Context,
    private val iconProvider: (Int) -> Bitmap
) : RadiusMarkerClusterer(context) {

    override fun buildClusterMarker(cluster: StaticCluster, mapView: MapView): Marker {
        val size = cluster.size
        setIcon(iconProvider(size))

        val marker = super.buildClusterMarker(cluster, mapView)
        marker.setOnMarkerClickListener { tapped, map ->
            map?.controller?.animateTo(tapped.position)
            map?.controller?.zoomIn()
            true
        }
        return marker
    }
}

private fun clusterColor(size: Int): Int {
    return when {
        size >= 100 -> 0xFFF57C00.toInt()
        size >= 50 -> 0xFFFFB300.toInt()
        size >= 20 -> 0xFF1E88E5.toInt()
        else -> 0xFF43A047.toInt()
    }
}

private fun createClusterBitmap(context: Context, color: Int): Bitmap {
    val size = (context.resources.displayMetrics.density * 44).toInt().coerceAtLeast(1)
    val bitmap = Bitmap.createBitmap(size, size, Bitmap.Config.ARGB_8888)
    val canvas = Canvas(bitmap)
    val drawable = GradientDrawable().apply {
        shape = GradientDrawable.OVAL
        setColor(color)
        setStroke((context.resources.displayMetrics.density * 2).toInt(), 0xFF4E342E.toInt())
    }
    drawable.setBounds(0, 0, size, size)
    drawable.draw(canvas)
    return bitmap
}

private suspend fun loadThumbnailBitmap(
    context: Context,
    imageLoader: ImageLoader,
    url: String
): Bitmap? {
    val request = ImageRequest.Builder(context)
        .data(url)
        .allowHardware(false)
        .size(80)
        .build()
    val result = imageLoader.execute(request)
    return (result as? SuccessResult)?.drawable?.let { drawableToBitmap(it) }
}

private fun createPinThumbnailBitmap(pin: Bitmap, thumb: Bitmap): Bitmap {
    val result = pin.copy(Bitmap.Config.ARGB_8888, true)
    val canvas = Canvas(result)
    val size = (result.width * 0.6f).coerceAtLeast(18f)
    val left = (result.width - size) / 2f
    val top = (result.height * 0.12f).coerceAtLeast(2f)
    val rect = RectF(left, top, left + size, top + size)

    val paint = Paint(Paint.ANTI_ALIAS_FLAG)
    canvas.drawOval(rect, paint)
    paint.xfermode = PorterDuffXfermode(PorterDuff.Mode.SRC_IN)
    canvas.drawBitmap(thumb, null, rect, paint)
    paint.xfermode = null
    paint.style = Paint.Style.STROKE
    paint.strokeWidth = 3f
    paint.color = 0xFFFFFFFF.toInt()
    canvas.drawOval(rect, paint)
    return result
}
