package com.guidexy.app.data

import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Query

interface ApiService {
    @GET("countries.php")
    suspend fun countries(): CountryResponse

    @GET("cities.php")
    suspend fun cities(@Query("country_code") countryCode: String): CityResponse

    @GET("categories.php")
    suspend fun categories(@Query("city") citySlug: String): CategoryResponse

    @GET("places.php")
    suspend fun places(
        @Query("city") citySlug: String,
        @Query("category") categorySlug: String
    ): PlacesResponse

    @GET("places_latest.php")
    suspend fun latest(@Query("country_code") countryCode: String, @Query("limit") limit: Int): PlacesLatestResponse

    @GET("places_popular.php")
    suspend fun popular(@Query("country_code") countryCode: String, @Query("limit") limit: Int): PlacesPopularResponse

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
        @Query("radius_km") radiusKm: Double? = null,
        @Query("limit") limit: Int? = null,
        @Query("page") page: Int? = null,
        @Query("per_page") perPage: Int? = null
    ): PlacesAlternateResponse

    @GET("place-detail.php")
    suspend fun placeDetail(@Query("id") placeId: Long): PlaceDetailResponse

    @POST("reviews.php")
    suspend fun submitReview(@Body request: ReviewSubmitRequest): ReviewSubmitResponse
}
