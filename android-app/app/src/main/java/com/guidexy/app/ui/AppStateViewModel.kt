package com.guidexy.app.ui

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.guidexy.app.data.*
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import kotlin.math.ceil

class AppStateViewModel(
    private val repository: PlacesRepository = PlacesRepository()
) : ViewModel() {

    private val _countries = MutableStateFlow<List<CountryDto>>(emptyList())
    val countries: StateFlow<List<CountryDto>> = _countries

    private val _cities = MutableStateFlow<List<CityDto>>(emptyList())
    val cities: StateFlow<List<CityDto>> = _cities

    private val _selectedCountry = MutableStateFlow<CountryDto?>(null)
    val selectedCountry: StateFlow<CountryDto?> = _selectedCountry

    private val _selectedCity = MutableStateFlow<CityDto?>(null)
    val selectedCity: StateFlow<CityDto?> = _selectedCity

    private val _latestPlaces = MutableStateFlow<List<PlaceDto>>(emptyList())
    val latestPlaces: StateFlow<List<PlaceDto>> = _latestPlaces

    private val _popularPlaces = MutableStateFlow<List<PlaceDto>>(emptyList())
    val popularPlaces: StateFlow<List<PlaceDto>> = _popularPlaces

    private val _mostViewedPlaces = MutableStateFlow<List<PlaceDto>>(emptyList())
    val mostViewedPlaces: StateFlow<List<PlaceDto>> = _mostViewedPlaces

    private val _categories = MutableStateFlow<List<CategoryDto>>(emptyList())
    val categories: StateFlow<List<CategoryDto>> = _categories

    private val _searchResults = MutableStateFlow<List<PlaceDto>>(emptyList())
    val searchResults: StateFlow<List<PlaceDto>> = _searchResults

    private val _searchHasMore = MutableStateFlow(false)
    val searchHasMore: StateFlow<Boolean> = _searchHasMore

    private val _searchLoading = MutableStateFlow(false)
    val searchLoading: StateFlow<Boolean> = _searchLoading

    private val _searchTotal = MutableStateFlow(0)
    val searchTotal: StateFlow<Int> = _searchTotal

    private val _searchPage = MutableStateFlow(1)
    val searchPage: StateFlow<Int> = _searchPage

    private val _loading = MutableStateFlow(false)
    val loading: StateFlow<Boolean> = _loading

    private var lastSearchParams: SearchParams? = null
    private var searchPageIndex = 1

    init {
        loadCountries()
    }

    fun loadCountries() {
        viewModelScope.launch {
            _loading.value = true
            runCatching { repository.getCountries() }
                .onSuccess { _countries.value = it.countries }
            _loading.value = false
        }
    }

    fun selectCountry(country: CountryDto) {
        _selectedCountry.value = country
        _selectedCity.value = null
        _categories.value = emptyList()
        _searchResults.value = emptyList()
        loadCities(country.code)
        loadCategories(country = country.code)
        loadHome(country.code)
    }

    fun selectCity(city: CityDto) {
        _selectedCity.value = city
        loadCategories(city = city.slug, country = _selectedCountry.value?.code)
    }

    fun clearCitySelection() {
        _selectedCity.value = null
        loadCategories(country = _selectedCountry.value?.code)
    }

    private fun loadCities(code: String) {
        viewModelScope.launch {
            _loading.value = true
            runCatching { repository.getCities(code) }
                .onSuccess { _cities.value = it.cities }
            _loading.value = false
        }
    }

    private fun loadCategories(city: String? = null, country: String? = null) {
        viewModelScope.launch {
            _loading.value = true
            runCatching { repository.getCategories(city = city, country = country) }
                .onSuccess { _categories.value = it.categories }
            _loading.value = false
        }
    }

    private fun loadHome(code: String) {
        viewModelScope.launch {
            _loading.value = true
            runCatching { repository.getLatest(code, PaginationConfig.homeHighlightsCount, 1) }
                .onSuccess { _latestPlaces.value = it.places }
            runCatching { repository.getPopular(code, PaginationConfig.homeHighlightsCount, 1) }
                .onSuccess { _popularPlaces.value = it.places }
            runCatching { repository.getMostViewed(code, PaginationConfig.homeHighlightsCount, 1) }
                .onSuccess { _mostViewedPlaces.value = it.places }
            _loading.value = false
        }
    }

    fun searchPlaces(
        query: String? = null,
        category: String? = null,
        city: String? = null,
        country: String? = null,
        sort: String? = null,
        lat: Double? = null,
        lng: Double? = null,
        radiusKm: Double? = null
    ) {
        lastSearchParams = SearchParams(query, category, city, country, sort, lat, lng, radiusKm)
        searchPageIndex = 1
        _searchPage.value = searchPageIndex
        _searchResults.value = emptyList()
        _searchHasMore.value = true
        _searchTotal.value = 0
        loadSearchPage(reset = true)
    }

    fun resetSearchState() {
        lastSearchParams = null
        searchPageIndex = 1
        _searchPage.value = 1
        _searchResults.value = emptyList()
        _searchHasMore.value = false
        _searchTotal.value = 0
    }

    fun loadMoreSearch() {
        if (_searchLoading.value || !_searchHasMore.value) return
        searchPageIndex += 1
        _searchPage.value = searchPageIndex
        loadSearchPage(reset = false)
    }

    private fun loadSearchPage(reset: Boolean) {
        viewModelScope.launch {
            _searchLoading.value = true
            val params = lastSearchParams ?: return@launch
            runCatching {
                repository.getAlternate(
                    query = params.query,
                    category = params.category,
                    city = params.city,
                    country = params.country,
                    sort = params.sort,
                    lat = params.lat,
                    lng = params.lng,
                    radiusKm = params.radiusKm,
                    perPage = PaginationConfig.resultsPerPage,
                    page = searchPageIndex
                )
            }.onSuccess { response ->
                val current = if (reset) emptyList() else _searchResults.value
                val combined = current + response.data.filter { place ->
                    current.none { it.id == place.id }
                }
                val total = response.total ?: combined.size
                _searchTotal.value = total
                val totalPages = ceil(total / PaginationConfig.resultsPerPage.toDouble()).toInt().coerceAtLeast(1)
                _searchResults.value = combined
                _searchPage.value = response.page ?: searchPageIndex
                _searchHasMore.value = searchPageIndex < totalPages
            }
            _searchLoading.value = false
        }
    }
}

private data class SearchParams(
    val query: String? = null,
    val category: String? = null,
    val city: String? = null,
    val country: String? = null,
    val sort: String? = null,
    val lat: Double? = null,
    val lng: Double? = null,
    val radiusKm: Double? = null
)
