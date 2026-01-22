package com.guidexy.app

import android.os.Build
import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.annotation.RequiresApi
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Scaffold
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
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
import com.guidexy.app.ui.screens.*

class MainActivity : ComponentActivity() {
    @RequiresApi(Build.VERSION_CODES.O)
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

@RequiresApi(Build.VERSION_CODES.O)
@Composable
private fun MainScaffold(navController: NavHostController, appState: AppStateViewModel) {
    val navBackStackEntry by navController.currentBackStackEntryAsState()
    val currentRoute = navBackStackEntry?.destination?.route

    Scaffold(
        // SABİT Bottom Bar
        bottomBar = {
            if (currentRoute != "country_select" && currentRoute != null) {
                BottomNavBar(
                    currentRoute = currentRoute,
                    onNavigate = { route ->
                        navController.navigate(route) {
                            popUpTo(navController.graph.findStartDestination().id) {
                                saveState = true
                            }
                            launchSingleTop = true
                            restoreState = true
                        }
                    }
                )
            }
        }
    ) { padding ->
        Box(modifier = Modifier.padding(padding)) {
            AppNavHost(navController, appState)
        }
    }
}

@RequiresApi(Build.VERSION_CODES.O)
@Composable
private fun AppNavHost(navController: NavHostController, appState: AppStateViewModel) {
    NavHost(
        navController = navController,
        startDestination = "country_select"
    ) {
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

        composable("home") {
            HomeScreen(appState, navController)
        }

        composable("search") {
            SearchScreen(appState, navController)
        }

        composable(
            route = "categories?slug={slug}",
            arguments = listOf(
                navArgument("slug") {
                    type = NavType.StringType
                    nullable = true
                    defaultValue = null
                }
            )
        ) { entry ->
            CategoryScreen(
                appState,
                navController,
                entry.arguments?.getString("slug")
            )
        }

        composable("cities") {
            CityScreen(appState, navController)
        }

        composable(
            route = "city_categories/{slug}?name={name}&total={total}",
            arguments = listOf(
                navArgument("slug") { type = NavType.StringType },
                navArgument("name") {
                    type = NavType.StringType
                    nullable = true
                    defaultValue = null
                },
                navArgument("total") { type = NavType.IntType; defaultValue = 0 }
            )
        ) { entry ->
            CityCategoriesScreen(
                citySlug = entry.arguments?.getString("slug") ?: "",
                cityName = entry.arguments?.getString("name"),
                cityTotal = entry.arguments?.getInt("total"),
                navController = navController,
                onBack = { navController.popBackStack() }
            )
        }

        composable(
            route = "category_places/{slug}?name={name}&total={total}&city={city}&cityName={cityName}",
            arguments = listOf(
                navArgument("slug") { type = NavType.StringType },
                navArgument("name") { type = NavType.StringType; nullable = true },
                navArgument("total") { type = NavType.IntType; defaultValue = 0 },
                navArgument("city") { type = NavType.StringType; nullable = true },
                navArgument("cityName") { type = NavType.StringType; nullable = true }
            )
        ) { entry ->
            CategoryPlacesScreen(
                categorySlug = entry.arguments?.getString("slug") ?: "",
                categoryName = entry.arguments?.getString("name"),
                categoryTotal = entry.arguments?.getInt("total"),
                citySlug = entry.arguments?.getString("city"),
                cityName = entry.arguments?.getString("cityName"),
                navController = navController,
                onBack = { navController.popBackStack() }
            )
        }

        composable("profile") {
            ProfileScreen(navController = navController)
        }

        composable("popular_list") {
            PlaceListScreen(
                listType = PlaceListType.Popular,
                viewModel = appState,
                navController = navController,
                onBack = { navController.popBackStack() }
            )
        }

        composable("latest_list") {
            PlaceListScreen(
                listType = PlaceListType.Latest,
                viewModel = appState,
                navController = navController,
                onBack = { navController.popBackStack() }
            )
        }

        composable("most_viewed_list") {
            PlaceListScreen(
                listType = PlaceListType.MostViewed,
                viewModel = appState,
                navController = navController,
                onBack = { navController.popBackStack() }
            )
        }

        composable(
            route = "place/{id}",
            arguments = listOf(navArgument("id") { type = NavType.LongType })
        ) { entry ->
            val placeId = entry.arguments?.getLong("id") ?: 0L
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
