export async function loadForCompleteProfile(token, currentToken, getUser, getNotifications) {
    if (!token) return null;

    const user = await getUser(token);
    if (user?.perfilCompleto !== true || currentToken() !== token) return null;

    return getNotifications(token);
}
