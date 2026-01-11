package com.guidexy.app

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.animation.slideInVertically
import androidx.compose.animation.slideOutVertically
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Scaffold
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.ui.Modifier
import androidx.compose.ui.input.nestedscroll.NestedScrollConnection
import androidx.compose.ui.input.nestedscroll.NestedScrollSource
import androidx.compose.ui.input.nestedscroll.nestedScroll
import androidx.compose.ui.geometry.Offset
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavGraph.Companion.findStartDestination
import androidx.navigation.NavHostController
import androidx.navigation.NavType
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import androidx.navigation.navArgument
import com.guidexy.app.ui.AppStateViewModel
import com.guidexy.app.ui.GuideXYTheme
import com.guidexy.app.ui.components.BottomNavBar
import com.guidexy.app.ui.screens.CategoryPlacesScreen
import com.guidexy.app.ui.screens.CategoryScreen
import com.guidexy.app.ui.screens.CityCategoriesScreen
import com.guidexy.app.ui.screens.CityScreen
import com.guidexy.app.ui.screens.CountrySelectScreen
import com.guidexy.app.ui.screens.HomeScreen
import com.guidexy.app.ui.screens.PlaceDetailScreen
import com.guidexy.app.ui.screens.PlaceListScreen
import com.guidexy.app.ui.screens.PlaceListType
import com.guidexy.app.ui.screens.ProfileScreen
import com.guidexy.app.ui.screens.SearchScreen

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent {
            GuideXYTheme {
                val navController = rememberNavController()
                val appState: AppStateViewModel = viewModel()
                MainScaffold(navController, appState)
            }
        }
    }
}

@Composable
private fun MainScaffold(navController: NavHostController, appState: AppStateViewModel) {
    val navBackStackEntry by navController.currentBackStackEntryAsState()
    val currentRoute = navBackStackEntry?.destination?.route
    val bottomBarVisible = remember { mutableStateOf(true) }
    val nestedScrollConnection = remember {
        object : NestedScrollConnection {
            override fun onPreScroll(available: Offset, source: NestedScrollSource): Offset {
                if (available.y < 0) {
                    bottomBarVisible.value = false
                } else if (available.y > 0) {
                    bottomBarVisible.value = true
                }
                return Offset.Zero
            }
        }
    }

    LaunchedEffect(currentRoute) {
        if (currentRoute != "search") {
            appState.resetSearchState()
        }
        bottomBarVisible.value = true
    }

    Scaffold(
        modifier = Modifier.nestedScroll(nestedScrollConnection),
        bottomBar = {
            if (currentRoute != "country_select") {
                AnimatedVisibility(
                    visible = bottomBarVisible.value,
                    enter = slideInVertically(initialOffsetY = { it }) + fadeIn(),
                    exit = slideOutVertically(targetOffsetY = { it }) + fadeOut()
                ) {
                    BottomNavBar(currentRoute = currentRoute, onNavigate = { route ->
                        navController.navigate(route) {
                            popUpTo(navController.graph.findStartDestination().id) { saveState = true }
                            launchSingleTop = true
                            restoreState = true
                        }
                    })
                }
            }
        }
    ) { padding ->
        Box(modifier = Modifier.padding(padding)) {
            AppNavHost(navController, appState)
        }
    }
}

@Composable
private fun AppNavHost(navController: NavHostController, appState: AppStateViewModel) {
    NavHost(
        navController = navController,
        startDestination = "country_select"
    ) {

        // Ülke seçimi ekranı
        composable("country_select") {
            CountrySelectScreen(
                viewModel = appState,
                onDone = {
                    navController.navigate("home") {
                        popUpTo("country_select") { inclusive = true }
                        launchSingleTop = true
                        restoreState = true
                    }
                }
            )
        }

        // Ana sayfa
        composable("home") {
            HomeScreen(appState, navController)
        }

        // Arama
        composable("search") {
            SearchScreen(appState, navController)
        }

        // Kategoriler + slug parametreli
        composable(
            route = "categories?slug={slug}",
            arguments = listOf(
                navArgument("slug") {
                    type = NavType.StringType
                    nullable = true
                    defaultValue = null
                }
            )
        ) { backStackEntry ->
            val slug = backStackEntry.arguments?.getString("slug")
            CategoryScreen(appState, navController, slug)
        }

        // Şehirler listesi
        composable("cities") {
            CityScreen(appState, navController)
        }

        // Şehirdeki kategoriler
        composable(
            route = "city_categories/{slug}?name={name}&total={total}",
            arguments = listOf(
                navArgument("slug") { type = NavType.StringType },
                navArgument("name") {
                    type = NavType.StringType
                    nullable = true
                    defaultValue = null
                },
                navArgument("total") {
                    type = NavType.IntType
                    defaultValue = 0
                }
            )
        ) { backStackEntry ->
            val slug = backStackEntry.arguments?.getString("slug") ?: ""
            val name = backStackEntry.arguments?.getString("name")
            val total = backStackEntry.arguments?.getInt("total")
            CityCategoriesScreen(
                citySlug = slug,
                cityName = name,
                cityTotal = total,
                navController = navController,
                onBack = { navController.popBackStack() }
            )
        }

        // Kategori işletme listesi
        composable(
            route = "category_places/{slug}?name={name}&total={total}&city={city}&cityName={cityName}",
            arguments = listOf(
                navArgument("slug") { type = NavType.StringType },
                navArgument("name") {
                    type = NavType.StringType
                    nullable = true
                    defaultValue = null
                },
                navArgument("total") {
                    type = NavType.IntType
                    defaultValue = 0
                },
                navArgument("city") {
                    type = NavType.StringType
                    nullable = true
                    defaultValue = null
                },
                navArgument("cityName") {
                    type = NavType.StringType
                    nullable = true
                    defaultValue = null
                }
            )
        ) { backStackEntry ->
            val slug = backStackEntry.arguments?.getString("slug") ?: ""
            val name = backStackEntry.arguments?.getString("name")
            val total = backStackEntry.arguments?.getInt("total")
            val citySlug = backStackEntry.arguments?.getString("city")
            val cityName = backStackEntry.arguments?.getString("cityName")
            CategoryPlacesScreen(
                categorySlug = slug,
                categoryName = name,
                categoryTotal = total,
                citySlug = citySlug,
                cityName = cityName,
                navController = navController,
                onBack = { navController.popBackStack() }
            )
        }

        // Profil
        composable("profile") {
            ProfileScreen()
        }

        // Popüler liste
        composable("popular_list") {
            PlaceListScreen(
                listType = PlaceListType.Popular,
                viewModel = appState,
                navController = navController,
                onBack = { navController.popBackStack() }
            )
        }

        // Son eklenen liste
        composable("latest_list") {
            PlaceListScreen(
                listType = PlaceListType.Latest,
                viewModel = appState,
                navController = navController,
                onBack = { navController.popBackStack() }
            )
        }

        // En çok görüntülenen
        composable("most_viewed_list") {
            PlaceListScreen(
                listType = PlaceListType.MostViewed,
                viewModel = appState,
                navController = navController,
                onBack = { navController.popBackStack() }
            )
        }

        // İşletme detay
        composable(
            route = "place/{id}",
            arguments = listOf(navArgument("id") { type = NavType.LongType })
        ) { backStackEntry ->
            val placeId = backStackEntry.arguments?.getLong("id") ?: 0L

            PlaceDetailScreen(
                placeId = placeId,
                onBack = {
                    val popped = navController.popBackStack()
                    if (!popped) {
                        navController.navigate("home") {
                            popUpTo(navController.graph.findStartDestination().id) {
                                saveState = true
                            }
                            launchSingleTop = true
                            restoreState = true
                        }
                    }
                }
            )
        }
    }
}
