package com.guidexy.app

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
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
import com.guidexy.app.ui.screens.CategoryPlacesScreen
import com.guidexy.app.ui.screens.CategoryScreen
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

    Scaffold(
        bottomBar = {
            if (currentRoute != "country_select") {
                BottomNavBar(currentRoute = currentRoute, onNavigate = { route ->
                    navController.navigate(route) {
                        popUpTo(navController.graph.findStartDestination().id) { saveState = true }
                        launchSingleTop = true
                        restoreState = true
                    }
                })
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

        // Kategori işletme listesi
        composable(
            route = "category_places/{slug}?name={name}",
            arguments = listOf(
                navArgument("slug") { type = NavType.StringType },
                navArgument("name") {
                    type = NavType.StringType
                    nullable = true
                    defaultValue = null
                }
            )
        ) { backStackEntry ->
            val slug = backStackEntry.arguments?.getString("slug") ?: ""
            val name = backStackEntry.arguments?.getString("name")
            CategoryPlacesScreen(
                categorySlug = slug,
                categoryName = name,
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
