package com.guidexy.app.ui

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.guidexy.app.data.*
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch

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

    private val _categories = MutableStateFlow<List<CategoryDto>>(emptyList())
    val categories: StateFlow<List<CategoryDto>> = _categories

    private val _searchResults = MutableStateFlow<List<PlaceDto>>(emptyList())
    val searchResults: StateFlow<List<PlaceDto>> = _searchResults

    private val _loading = MutableStateFlow(false)
    val loading: StateFlow<Boolean> = _loading

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
        loadHome(country.code)
    }

    fun selectCity(city: CityDto) {
        _selectedCity.value = city
        loadCategories(city.slug)
    }

    private fun loadCities(code: String) {
        viewModelScope.launch {
            _loading.value = true
            runCatching { repository.getCities(code) }
                .onSuccess { _cities.value = it.cities }
            _loading.value = false
        }
    }

    private fun loadCategories(city: String) {
        viewModelScope.launch {
            _loading.value = true
            runCatching { repository.getCategories(city) }
                .onSuccess { _categories.value = it.categories }
            _loading.value = false
        }
    }

    private fun loadHome(code: String) {
        viewModelScope.launch {
            _loading.value = true
            runCatching { repository.getLatest(code, 10) }
                .onSuccess { _latestPlaces.value = it.places }
            runCatching { repository.getPopular(code, 10) }
                .onSuccess { _popularPlaces.value = it.places }
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
        viewModelScope.launch {
            _loading.value = true
            runCatching {
                repository.getAlternate(
                    query = query,
                    category = category,
                    city = city,
                    country = country,
                    sort = sort,
                    lat = lat,
                    lng = lng,
                    radiusKm = radiusKm,
                    limit = 100
                )
            }.onSuccess { response ->
                _searchResults.value = response.data
            }
            _loading.value = false
        }
    }
}
