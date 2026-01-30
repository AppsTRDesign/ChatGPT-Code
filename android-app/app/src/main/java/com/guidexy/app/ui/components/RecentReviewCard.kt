package com.guidexy.app.ui.components

import androidx.compose.animation.animateContentSize
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Favorite
import androidx.compose.material.icons.filled.Place
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.filled.StarBorder
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import coil.compose.SubcomposeAsyncImage
import com.guidexy.app.data.RecentReviewDto
import java.text.SimpleDateFormat
import java.util.Locale

private val ReviewStarGold = Color(0xFFFFB800)

@Composable
fun RecentReviewCard(
    review: RecentReviewDto,
    modifier: Modifier = Modifier,
    onClick: () -> Unit,
    onPhotoClick: (List<String>, String) -> Unit
) {
    var expanded by rememberSaveable(review.review_ref) { mutableStateOf(false) }

    val text = review.review_text?.trim().orEmpty()
    val hasLongText = text.split(" ").size > 24
    val displayText =
        if (expanded || !hasLongText)
            text
        else
            text.split(" ").take(24).joinToString(" ") + "…"

    val hasDetails =
        review.text_extra.isNotEmpty() ||
            review.review_photo_urls.isNotEmpty()

    Card(
        modifier = modifier
            .heightIn(min = 240.dp)
            .clickable { onClick() },
        shape = RoundedCornerShape(20.dp),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.35f)
        )
    ) {
        Column(
            modifier = Modifier
                .padding(16.dp)
                .animateContentSize(),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {

            /* 👤 USER HEADER */
            Row(verticalAlignment = Alignment.CenterVertically) {
                SubcomposeAsyncImage(
                    model = review.profile_photo_url,
                    contentDescription = "Profil",
                    modifier = Modifier
                        .size(42.dp)
                        .clip(CircleShape)
                        .background(MaterialTheme.colorScheme.surfaceVariant),
                    contentScale = ContentScale.Crop
                )
                Spacer(Modifier.width(10.dp))
                Column(Modifier.weight(1f)) {
                    Text(
                        text = review.author_name ?: "Kullanıcı",
                        style = MaterialTheme.typography.titleSmall.copy(fontWeight = FontWeight.Bold)
                    )
                    review.created_at?.let {
                        Text(
                            text = formatReviewDate(it),
                            style = MaterialTheme.typography.labelSmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                }
                LikesBadge(review.likes_count)
            }

            /* ⭐ RATING */
            Row(verticalAlignment = Alignment.CenterVertically) {
                RatingStars(review.rating)
                Spacer(Modifier.width(6.dp))
                Text(
                    text = review.rating?.let { String.format("%.1f", it) } ?: "-",
                    style = MaterialTheme.typography.labelSmall.copy(fontWeight = FontWeight.Bold)
                )
            }

            /* 🏪 PLACE */
            Text(
                text = review.place_name,
                style = MaterialTheme.typography.titleSmall.copy(fontWeight = FontWeight.Bold),
                maxLines = 1,
                overflow = TextOverflow.Ellipsis
            )

            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(Icons.Default.Place, null, modifier = Modifier.size(14.dp))
                Spacer(Modifier.width(4.dp))
                Text(
                    text = listOfNotNull(review.business_type, review.city_name).joinToString(" • "),
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }

            /* 💬 REVIEW TEXT */
            if (displayText.isNotBlank()) {
                Text(
                    text = displayText,
                    style = MaterialTheme.typography.bodySmall,
                    lineHeight = 18.sp
                )
            }

            /* 🔘 TOGGLE */
            if (hasLongText || hasDetails) {
                TextButton(onClick = { expanded = !expanded }) {
                    Text(if (expanded) "Detayları Gizle" else "Detayları Gör")
                }
            }

            /* 🔽 DETAILS */
            if (expanded) {

                /* 📋 TEXT EXTRA */
                if (review.text_extra.isNotEmpty()) {
                    Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {

                        Text(
                            text = "Detaylar",
                            style = MaterialTheme.typography.labelLarge.copy(
                                fontWeight = FontWeight.Bold
                            )
                        )

                        review.text_extra.forEach { extra ->
                            Column(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .background(
                                        MaterialTheme.colorScheme.surface.copy(alpha = 0.5f),
                                        RoundedCornerShape(12.dp)
                                    )
                                    .padding(horizontal = 12.dp, vertical = 10.dp),
                                verticalArrangement = Arrangement.spacedBy(4.dp)
                            ) {

                                /* 🔹 BAŞLIK */
                                Text(
                                    text = extra.label,
                                    style = MaterialTheme.typography.labelSmall.copy(
                                        fontWeight = FontWeight.SemiBold
                                    ),
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )

                                /* 🔸 AÇIKLAMA / DEĞER */
                                extra.value?.takeIf { it.isNotBlank() }?.let { value ->
                                    Text(
                                        text = value,
                                        style = MaterialTheme.typography.bodySmall.copy(
                                            fontWeight = FontWeight.Medium
                                        ),
                                        color = MaterialTheme.colorScheme.primary,
                                        lineHeight = 18.sp
                                    )
                                }
                            }
                        }
                    }
                }

                /* 🖼️ PHOTOS */
                if (review.review_photo_urls.isNotEmpty()) {
                    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {

                        Text(
                            text = "Paylaşılan Fotoğraflar",
                            style = MaterialTheme.typography.labelLarge.copy(
                                fontWeight = FontWeight.Bold
                            )
                        )

                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            review.review_photo_urls.take(3).forEach { url ->
                                SubcomposeAsyncImage(
                                    model = url,
                                    contentDescription = null,
                                    modifier = Modifier
                                        .size(72.dp)
                                        .clip(RoundedCornerShape(14.dp))
                                        .clickable {
                                            onPhotoClick(review.review_photo_urls, url)
                                        },
                                    contentScale = ContentScale.Crop
                                )
                            }
                        }
                    }
                }
            }
        }
    }
}

/* ⭐ STARS */
@Composable
private fun RatingStars(rating: Double?) {
    val filled = (rating ?: 0.0).toInt().coerceIn(0, 5)
    Row(horizontalArrangement = Arrangement.spacedBy(2.dp)) {
        repeat(5) {
            Icon(
                imageVector = if (it < filled) Icons.Default.Star else Icons.Default.StarBorder,
                contentDescription = null,
                tint = ReviewStarGold,
                modifier = Modifier.size(14.dp)
            )
        }
    }
}

/* ❤️ LIKES */
@Composable
private fun LikesBadge(count: Int) {
    Row(verticalAlignment = Alignment.CenterVertically) {
        Icon(
            Icons.Default.Favorite,
            null,
            tint = ReviewStarGold,
            modifier = Modifier.size(14.dp)
        )
        Spacer(Modifier.width(4.dp))
        Text(
            text = count.toString(),
            style = MaterialTheme.typography.labelSmall.copy(fontWeight = FontWeight.Bold)
        )
    }
}

private fun formatReviewDate(dateString: String): String {
    return try {
        val inputFormat = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.getDefault())
        val outputFormat = SimpleDateFormat("dd.MM.yyyy HH:mm", Locale.getDefault())
        val date = inputFormat.parse(dateString)
        date?.let { outputFormat.format(it) } ?: dateString
    } catch (e: Exception) {
        dateString
    }
}
