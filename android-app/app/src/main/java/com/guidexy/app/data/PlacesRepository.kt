package com.guidexy.app.data

class PlacesRepository(private val api: ApiService = ApiClient.service) {
    suspend fun getCountries() = api.countries()
    suspend fun getCities(code: String) = api.cities(code)
    suspend fun getCategories(city: String? = null, country: String? = null) =
        api.categories(citySlug = city, country = country)
    suspend fun getPlaces(city: String? = null, category: String, country: String? = null) =
        api.places(citySlug = city, categorySlug = category, country = country)
    suspend fun getLatest(code: String, perPage: Int, page: Int) = api.latest(code, perPage, page)
    suspend fun getPopular(code: String, perPage: Int, page: Int) = api.popular(code, perPage, page)
    suspend fun getMostViewed(code: String, perPage: Int, page: Int) = api.mostViewed(code, perPage, page)
    suspend fun getAlternate(
        query: String? = null,
        category: String? = null,
        city: String? = null,
        country: String? = null,
        sort: String? = null,
        lat: Double? = null,
        lng: Double? = null,
        radiusKm: Double? = null,
        limit: Int? = null,
        page: Int? = null,
        perPage: Int? = null
    ) = api.placesAlternate(
        query = query,
        categorySlug = category,
        citySlug = city,
        countrySlug = country,
        sort = sort,
        lat = lat,
        lng = lng,
        radiusKm = radiusKm,
        limit = limit,
        page = page,
        perPage = perPage
    )
    suspend fun getPlaceDetail(placeId: Long) = api.placeDetail(placeId)
    suspend fun submitReview(request: ReviewSubmitRequest) = api.submitReview(request)
}
