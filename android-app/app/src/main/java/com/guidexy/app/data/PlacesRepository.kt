package com.guidexy.app.data

class PlacesRepository(private val api: ApiService = ApiClient.service) {
    suspend fun getCountries() = api.countries()
    suspend fun getCities(code: String) = api.cities(code)
    suspend fun getCategories(city: String) = api.categories(city)
    suspend fun getPlaces(city: String, category: String) = api.places(city, category)
    suspend fun getLatest(code: String, limit: Int) = api.latest(code, limit)
    suspend fun getPopular(code: String, limit: Int) = api.popular(code, limit)
    suspend fun getPlaceDetail(placeId: Long) = api.placeDetail(placeId)
    suspend fun submitReview(request: ReviewSubmitRequest) = api.submitReview(request)
}
