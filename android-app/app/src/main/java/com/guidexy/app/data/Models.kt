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
    val total: Int? = null,
    val page: Int? = null,
    val per_page: Int? = null,
    val error: String?
)

data class PlacesPopularResponse(
    val success: Boolean,
    val places: List<PlaceDto>,
    val total: Int? = null,
    val page: Int? = null,
    val per_page: Int? = null,
    val error: String?
)

data class PlacesMostViewedResponse(
    val success: Boolean,
    val places: List<PlaceDto>,
    val total: Int? = null,
    val page: Int? = null,
    val per_page: Int? = null,
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
    val opening_hours: Any? = null,  // FIX
    val busy_hours: Any? = null,     // FIX
    val business_image: String?,
    val city_name: String?,
    val city_slug: String?,
    val combined_rating: Double? = null,
    val total_reviews: Int? = null,
    val views: Int? = null,
    val distance_m: Double? = null,
    val formatted_phone_number: String? = null,
    val website: String? = null
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
    val description: String? = null,
    val city_name: String? = null,
    val formatted_address: String? = null,
    val latitude: String? = null,
    val longitude: String? = null,
    val rating: Double? = 0.0,
    val user_ratings_total: Int? = 0,
    val combined_rating: Double? = 0.0,
    val total_reviews: Int? = 0,
    val views: Int? = 0,
    val formatted_phone_number: String? = null,
    val website: String? = null,
    val business_type: String? = null,
    val business_image: String? = null,
    val current_status: PlaceStatusDto? = null,
    val reviews: Any? = null,        // EXTRA FIX → bu işletmede NULL ama bazı yerlerde array JSON olabilir
    val busy_hours: Any? = null,     // FIX
    val opening_hours: Any? = null,  // FIX
    val claimed_by: Long? = null
)

data class PlaceStatusDto(
    val status: String?,
    val text: String?
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
    val category_id: Long? = null,
    val place_id: Long? = null,
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
    val relative_time_raw: String? = null,
    val created_at: String? = null,
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
    val created_at: String?,
    val profile_photo_url: String?
)

data class ReviewSubmitRequest(
    val place_id: Long,
    val user_id: Long,
    val user_name: String,
    val user_email: String?,
    val user_role: String?,
    val rating: Int,
    val text: String?,
    val text_extra: Map<String, String>?,
    val review_photo_urls: List<String>?
)

data class ReviewSubmitResponse(
    val success: Boolean? = null,
    val status: String? = null,
    val error: String? = null
)

data class AuthRequest(
    val email: String,
    val password: String
)

data class RegisterRequest(
    val name: String,
    val email: String,
    val password: String
)

data class AuthResponse(
    val success: Boolean? = null,
    val error: String? = null,
    val user: UserProfileDto? = null
)

data class ProfileResponse(
    val success: Boolean? = null,
    val user: UserProfileDto? = null,
    val error: String? = null
)

data class UserProfileDto(
    val id: Long,
    val name: String,
    val email: String,
    val avatar_url: String? = null,
    val profile_photo: String? = null,
    val role: String? = null
)

data class ProfileUpdateRequest(
    val user_id: Long,
    val name: String,
    val email: String,
    val password: String? = null
)

data class ProfileUpdateResponse(
    val success: Boolean? = null,
    val user: UserProfileDto? = null,
    val error: String? = null
)

data class AvatarUploadResponse(
    val status: String? = null,
    val url: String? = null,
    val error: String? = null
)

data class ReviewReplyDto(
    val reply_text: String?,
    val replied_by: Int?,
    val replied_role: String?,
    val created_at: String?,
    val updated_at: String?
)

data class PlaceReviewDto(
    val source: String,
    val review_ref: String,
    val id: Long? = null,
    val author_name: String?,
    val rating: Double?,
    val relative_time: String?,
    val text: String?,
    val profile_photo_url: String?,
    val text_extra: Map<String, String>?,
    val review_photo_urls: List<String>?,
    val reply: ReviewReplyDto?
)

data class PlaceReviewsResponse(
    val reviews: List<PlaceReviewDto>,
    val total_pages: Int,
    val total: Int
)

data class UserReviewItemDto(
    val id: Long,
    val place_id: Long,
    val place_name: String?,
    val place_image: String? = null,
    val rating: Int,
    val text: String?,
    val created_at: String?,
    val status: String?
)

data class UserReviewsResponse(
    val success: Boolean? = null,
    val reviews: List<UserReviewItemDto> = emptyList(),
    val total: Int = 0,
    val total_pages: Int = 1,
    val page: Int = 1
)

data class OwnerPlaceDto(
    val id: Long,
    val name: String,
    val business_image: String? = null,
    val formatted_address: String? = null,
    val city_name: String? = null
)

data class OwnerPlacesResponse(
    val success: Boolean? = null,
    val places: List<OwnerPlaceDto> = emptyList(),
    val total: Int = 0,
    val total_pages: Int = 1,
    val page: Int = 1
)

data class ReviewReplyRequest(
    val type: String = "review_reply",
    val place_id: Long,
    val source: String,
    val review_ref: String,
    val reply_text: String,
    val user_id: Long,
    val user_role: String?
)
