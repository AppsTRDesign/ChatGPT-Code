package com.guidexy.app.data

data class CountryResponse(
    val success: Boolean,
    val countries: List<CountryDto>,
    val error: String?
)

data class CountryDto(
    val code: String,
    val name: String,
    val total_places: Int
)

data class CityResponse(
    val success: Boolean,
    val cities: List<CityDto>,
    val error: String?
)

data class CityDto(
    val city: String,
    val slug: String,
    val total_places: Int
)

data class CategoryResponse(
    val city: String,
    val categories: List<CategoryDto>
)

data class CategoryDto(
    val business_type: String?,
    val category_slug: String?,
    val total: Int
)

data class PlacesResponse(
    val city: String,
    val category: String,
    val places: List<PlaceDto>
)

data class PlacesLatestResponse(
    val success: Boolean,
    val places: List<PlaceDto>,
    val error: String?
)

data class PlacesPopularResponse(
    val success: Boolean,
    val places: List<PlaceDto>,
    val error: String?
)

data class PlacesAlternateResponse(
    val data: List<PlaceDto>,
    val total: Int? = null,
    val page: Int? = null,
    val per_page: Int? = null
)

data class PlaceDto(
    val id: Long,
    val name: String,
    val formatted_address: String?,
    val latitude: String?,
    val longitude: String?,
    val rating: Double?,
    val description: String?,
    val user_ratings_total: Int?,
    val business_type: String?,
    val category_slug: String?,
    val opening_hours: String?,
    val busy_hours: String?,
    val business_image: String?,
    val city_name: String?,
    val city_slug: String?,
    val combined_rating: Double? = null,
    val total_reviews: Int? = null,
    val views: Int? = null,
    val distance_m: Double? = null
)

data class PlaceDetailResponse(
    val place: PlaceDetailDto,
    val hours: List<PlaceHoursDto> = emptyList(),
    val social_links: List<PlaceSocialDto> = emptyList(),
    val services: List<PlaceServiceDto> = emptyList(),
    val service_categories: List<PlaceServiceCategoryDto> = emptyList(),
    val service_items: List<PlaceServiceItemDto> = emptyList(),
    val service_item_prices: List<PlaceServiceItemPriceDto> = emptyList(),
    val galleries: List<PlaceGalleryDto> = emptyList(),
    val gallery_images: List<PlaceGalleryImageDto> = emptyList(),
    val knows_about: List<PlaceKnowsAboutDto> = emptyList(),
    val google_reviews: List<GoogleReviewDto> = emptyList(),
    val user_reviews: List<UserReviewDto> = emptyList()
)

data class PlaceDetailDto(
    val id: Long,
    val name: String,
    val description: String?,
    val city_name: String?,
    val formatted_address: String?,
    val latitude: String?,
    val longitude: String?,
    val rating: Double?,
    val user_ratings_total: Int?,
    val formatted_phone_number: String?,
    val website: String?,
    val business_type: String?,
    val business_image: String?,
    val busy_hours: String?,
    val opening_hours: String?
)

data class PlaceHoursDto(
    val day: Int,
    val open_time: String?,
    val close_time: String?,
    val is_24h: Int,
    val is_closed: Int
)

data class PlaceSocialDto(
    val label: String,
    val url: String
)

data class PlaceServiceDto(
    val id: Long,
    val title: String
)

data class PlaceServiceCategoryDto(
    val id: Long,
    val service_id: Long,
    val title: String
)

data class PlaceServiceItemDto(
    val id: Long,
    val title: String,
    val description: String?,
    val price: String?,
    val currency_symbol: String?,
    val button_label: String?,
    val button_url: String?,
    val image_url: String?
)

data class PlaceServiceItemPriceDto(
    val id: Long,
    val item_id: Long,
    val title: String,
    val price: String,
    val currency_symbol: String
)

data class PlaceGalleryDto(
    val id: Long,
    val title: String
)

data class PlaceGalleryImageDto(
    val id: Long,
    val gallery_id: Long,
    val image_url: String,
    val thumb_url: String
)

data class PlaceKnowsAboutDto(
    val id: Long,
    val category: String,
    val value: String
)

data class GoogleReviewDto(
    val author_name: String?,
    val rating: Int?,
    val relative_time: String?,
    val text: String?,
    val profile_photo_url: String?,
    val text_extra: Map<String, String>?,
    val review_photo_urls: List<String>?
)

data class UserReviewDto(
    val author_name: String?,
    val rating: Int?,
    val review_text: String?,
    val text_extra: Map<String, String>?,
    val review_photo_urls: List<String>?,
    val created_at: String?
)

data class ReviewSubmitRequest(
    val place_id: Long,
    val name: String,
    val rating: Int,
    val review: String?,
    val email: String?,
    val text_extra: Map<String, String>?,
    val review_photo_urls: List<String>?
)

data class ReviewSubmitResponse(
    val success: Boolean? = null,
    val error: String? = null
)
