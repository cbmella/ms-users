<?php

/**
 * @OA\Info(
 *    title="Swagger with Lumen",
 *    version="1.0.0",
 * )
 *
 * @OA\Server(
 *     url="http://localhost/authentication/public",
 *     description="Local server"
 * )
 */

/**
 * @OA\Post(
 *     path="/auth/login",
 *     summary="Login user",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/x-www-form-urlencoded",
 *             @OA\Schema(
 *                 @OA\Property(property="email", type="string", example="user@example.com"),
 *                 @OA\Property(property="password", type="string", example="password")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful login"
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/auth/register",
 *     summary="Register new user",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/x-www-form-urlencoded",
 *             @OA\Schema(
 *                 @OA\Property(property="name", type="string", example="test2"),
 *                 @OA\Property(property="email", type="string", example="user2422@example.com"),
 *                 @OA\Property(property="password", type="string", example="password"),
 *                 @OA\Property(property="password_confirmation", type="string", example="password")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful registration"
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/auth/me",
 *     summary="Get authenticated user details",
 *     security={
 *         {"bearerAuth": {}}
 *     },
 *     @OA\Response(
 *         response=200,
 *         description="Authenticated user details"
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/auth/logout",
 *     summary="Logout user",
 *     security={
 *         {"bearerAuth": {}}
 *     },
 *     @OA\Response(
 *         response=200,
 *         description="Successful logout"
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/auth/test-token-ttl",
 *     summary="Test token TTL",
 *     security={
 *         {"bearerAuth": {}}
 *     },
 *     @OA\Response(
 *         response=200,
 *         description="Token TTL test result"
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/auth/refresh",
 *     summary="Refresh authentication token",
 *     security={
 *         {"bearerAuth": {}}
 *     },
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/x-www-form-urlencoded",
 *             @OA\Schema(
 *                 @OA\Property(property="refresh_token", type="string", example="YourRefreshTokenHere")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Token refreshed"
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/auth/validate-token",
 *     summary="Validate authentication token",
 *     security={
 *         {"bearerAuth": {}}
 *     },
 *     @OA\Response(
 *         response=200,
 *         description="Token is valid"
 *     )
 * )
 */

// Bearer authentication definition
/**
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
