package com.guidexy.app.data

object UserSession {
    @Volatile
    var currentUser: UserProfileDto? = null

    fun update(user: UserProfileDto?) {
        currentUser = user
    }

    fun clear() {
        currentUser = null
    }
}
