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
import com.guidexy.app.ui.screens.CategoryScreen
import com.guidexy.app.ui.screens.CountrySelectScreen
import com.guidexy.app.ui.screens.HomeScreen
import com.guidexy.app.ui.screens.PlaceDetailScreen
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
                        popUpTo("home") { saveState = true }
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
    NavHost(navController = navController, startDestination = "country_select") {
        composable("country_select") { CountrySelectScreen(appState) { navController.navigate("home") } }
        composable("home") { HomeScreen(appState, navController) }
        composable("search") { SearchScreen(appState, navController) }
        composable(
            "categories?slug={slug}",
            arguments = listOf(navArgument("slug") { type = NavType.StringType; nullable = true })
        ) { backStackEntry ->
            CategoryScreen(appState, navController, backStackEntry.arguments?.getString("slug"))
        }
        composable("profile") { ProfileScreen() }
        composable("place/{id}") { backStackEntry ->
            val placeId = backStackEntry.arguments?.getString("id")?.toLongOrNull() ?: 0L
            PlaceDetailScreen(
                placeId = placeId,
                onBack = {
                    navController.navigate("home") {
                        popUpTo("home") { inclusive = false }
                        launchSingleTop = true
                    }
                }
            )
        }
    }
}
