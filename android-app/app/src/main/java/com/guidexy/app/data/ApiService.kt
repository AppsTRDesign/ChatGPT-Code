package com.guidexy.app.data

import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.Multipart
import retrofit2.http.POST
import retrofit2.http.Query
import retrofit2.http.Part

interface ApiService {
    @GET("countries.php")
    suspend fun countries(): CountryResponse

    @GET("cities.php")
    suspend fun cities(@Query("country_code") countryCode: String): CityResponse

    @GET("categories.php")
    suspend fun categories(
        @Query("city") citySlug: String? = null,
        @Query("country") country: String? = null
    ): CategoryResponse

    @GET("places.php")
    suspend fun places(
        @Query("city") citySlug: String? = null,
        @Query("category") categorySlug: String,
        @Query("country") country: String? = null
    ): PlacesResponse

    @GET("places_latest.php")
    suspend fun latest(
        @Query("country_code") countryCode: String,
        @Query("per_page") perPage: Int,
        @Query("page") page: Int
    ): PlacesLatestResponse

    @GET("places_popular.php")
    suspend fun popular(
        @Query("country_code") countryCode: String,
        @Query("per_page") perPage: Int,
        @Query("page") page: Int
    ): PlacesPopularResponse

    @GET("places_most_viewed.php")
    suspend fun mostViewed(
        @Query("country_code") countryCode: String,
        @Query("per_page") perPage: Int,
        @Query("page") page: Int
    ): PlacesMostViewedResponse

    @GET("places_favorites.php")
    suspend fun favorites(
        @Query("country_code") countryCode: String,
        @Query("per_page") perPage: Int,
        @Query("page") page: Int,
        @Query("sort") sort: String? = null,
        @Query("lat") lat: Double? = null,
        @Query("lng") lng: Double? = null
    ): PlacesFavoritesResponse

    @GET("places_alternate.php")
    suspend fun placesAlternate(
        @Query("mode") mode: String = "map",
        @Query("q") query: String? = null,
        @Query("category") categorySlug: String? = null,
        @Query("city") citySlug: String? = null,
        @Query("country") countrySlug: String? = null,
        @Query("sort") sort: String? = null,
        @Query("lat") lat: Double? = null,
        @Query("lng") lng: Double? = null,
        @Query("limit") limit: Int? = null,
        @Query("page") page: Int? = null,
        @Query("per_page") perPage: Int? = null
    ): PlacesAlternateResponse

    @GET("recent_reviews.php")
    suspend fun recentReviews(
        @Query("per_page") perPage: Int,
        @Query("page") page: Int,
        @Query("country_code") countryCode: String? = null
    ): RecentReviewsResponse

    @GET("place-detail.php")
    suspend fun placeDetail(
        @Query("id") placeId: Long,
        @Query("user_id") userId: Long? = null
    ): PlaceDetailResponse

    @POST("submit_review.php")
    suspend fun submitReview(@Body request: ReviewSubmitRequest): ReviewSubmitResponse

    @POST("submit_review.php")
    suspend fun submitReviewReply(@Body request: ReviewReplyRequest): ReviewSubmitResponse

    @POST("submit_review.php")
    suspend fun submitReviewLike(@Body request: ReviewLikeRequest): ReviewLikeResponse

    @POST("submit_review.php")
    suspend fun submitPlaceFavorite(@Body request: PlaceFavoriteRequest): PlaceFavoriteResponse

    @POST("login.php")
    suspend fun login(@Body request: AuthRequest): AuthResponse

    @POST("register.php")
    suspend fun register(@Body request: RegisterRequest): AuthResponse

    @POST("social_login.php")
    suspend fun socialLogin(@Body request: SocialAuthRequest): AuthResponse

    @POST("logout.php")
    suspend fun logout(): AuthResponse

    @GET("profile.php")
    suspend fun profile(@Query("user_id") userId: Long): ProfileResponse

    @POST("profile.php")
    suspend fun updateProfile(@Body request: ProfileUpdateRequest): ProfileUpdateResponse

    @GET("reviews.php")
    suspend fun placeReviews(
        @Query("place_id") placeId: Long,
        @Query("s") page: Int,
        @Query("sort") sort: String,
        @Query("user_id") userId: Long? = null,
        @Query("status") status: String? = null
    ): PlaceReviewsResponse

    @GET("user_reviews.php")
    suspend fun userReviews(
        @Query("user_id") userId: Long,
        @Query("s") page: Int,
        @Query("limit") limit: Int = 10,
        @Query("reply_status") replyStatus: String? = null,
        @Query("status") status: String? = null
    ): UserReviewsResponse

    @GET("owner_places.php")
    suspend fun ownerPlaces(
        @Query("user_id") userId: Long,
        @Query("s") page: Int,
        @Query("limit") limit: Int = 10
    ): OwnerPlacesResponse

    @GET("place_favorites.php")
    suspend fun placeFavorites(
        @Query("user_id") userId: Long,
        @Query("s") page: Int,
        @Query("limit") limit: Int = 10,
        @Query("sort") sort: String? = null,
        @Query("lat") lat: Double? = null,
        @Query("lng") lng: Double? = null
    ): PlaceFavoritesResponse

    @Multipart
    @POST("submit_review_image.php")
    suspend fun uploadReviewImage(
        @Part("place_id") placeId: okhttp3.RequestBody,
        @Part("user_id") userId: okhttp3.RequestBody,
        @Part file: okhttp3.MultipartBody.Part
    ): AvatarUploadResponse

    @Multipart
    @POST("profile_avatar.php")
    suspend fun uploadAvatar(
        @Part("user_id") userId: okhttp3.RequestBody,
        @Part file: okhttp3.MultipartBody.Part
    ): AvatarUploadResponse
}
