package com.guidexy.app.ui.components

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxHeight
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Place
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.filled.StarBorder
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Icon
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
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
fun HomeRecentReviewCard(
    review: RecentReviewDto,
    modifier: Modifier = Modifier,
    maxWords: Int = 22,
    onClick: () -> Unit
) {
    val text = review.review_text?.trim().orEmpty()

    val shortText = rememberShortText(
        text = text,
        maxWords = maxWords
    )

    Card(
        modifier = modifier
            .width(280.dp)
            .heightIn(min = 220.dp)
            .clickable { onClick() },
        shape = RoundedCornerShape(20.dp),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.35f)
        )
    ){
        Column(
            modifier = Modifier
                .fillMaxHeight()
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp)
        ) {

            /* 👤 USER */
            Row(verticalAlignment = Alignment.CenterVertically) {
                SubcomposeAsyncImage(
                    model = review.profile_photo_url,
                    contentDescription = "Profil",
                    modifier = Modifier
                        .size(40.dp)
                        .clip(CircleShape)
                        .background(MaterialTheme.colorScheme.surfaceVariant),
                    contentScale = ContentScale.Crop
                )

                Spacer(modifier = Modifier.width(10.dp))

                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        text = review.author_name ?: "Kullanıcı",
                        style = MaterialTheme.typography.titleSmall.copy(
                            fontWeight = FontWeight.Bold
                        ),
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                    review.created_at?.let {
                        Text(
                            text = formatReviewDate(it),
                            style = MaterialTheme.typography.labelSmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                }
            }

            /* ⭐ RATING */
            Row(verticalAlignment = Alignment.CenterVertically) {
                RatingStarsSmall(review.rating)
                Spacer(modifier = Modifier.width(6.dp))
                Text(
                    text = review.rating?.let { String.format("%.1f", it) } ?: "-",
                    style = MaterialTheme.typography.labelSmall.copy(
                        fontWeight = FontWeight.Bold
                    )
                )
            }

            /* 🏪 PLACE */
            Text(
                text = review.place_name,
                style = MaterialTheme.typography.titleSmall.copy(
                    fontWeight = FontWeight.Bold
                ),
                maxLines = 1,
                overflow = TextOverflow.Ellipsis
            )

            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(
                    Icons.Default.Place,
                    contentDescription = null,
                    modifier = Modifier.size(14.dp),
                    tint = MaterialTheme.colorScheme.onSurfaceVariant
                )
                Spacer(modifier = Modifier.width(4.dp))
                Text(
                    text = listOfNotNull(
                        review.business_type,
                        review.city_name
                    ).joinToString(" • "),
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )
            }

            /* 💬 SHORT REVIEW TEXT */
            if (shortText.isNotBlank()) {
                Text(
                    text = shortText,
                    style = MaterialTheme.typography.bodySmall,
                    lineHeight = 18.sp,
                    maxLines = 3,
                    overflow = TextOverflow.Ellipsis
                )
            }
        }
    }
}

/* 🔤 CÜMLE KISALTMA */
@Composable
private fun rememberShortText(
    text: String,
    maxWords: Int
): String {
    if (text.isBlank()) return ""

    val words = text.split("\\s+".toRegex())
    return if (words.size <= maxWords) {
        text
    } else {
        words.take(maxWords).joinToString(" ") + "…"
    }
}

/* ⭐ SMALL STAR */
@Composable
private fun RatingStarsSmall(rating: Double?) {
    val filled = (rating ?: 0.0).toInt().coerceIn(0, 5)
    Row(horizontalArrangement = Arrangement.spacedBy(2.dp)) {
        repeat(5) { index ->
            Icon(
                imageVector = if (index < filled) Icons.Default.Star else Icons.Default.StarBorder,
                contentDescription = null,
                tint = ReviewStarGold,
                modifier = Modifier.size(14.dp)
            )
        }
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
