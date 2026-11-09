// Crops the middle square of a photo and shrinks it, so a phone photo
// uploads as a file of around 100 KB instead of several MB.
export async function squarePhoto(file, size = 600, quality = 0.85) {
    const bitmap = await createImageBitmap(file);
    const side = Math.min(bitmap.width, bitmap.height);
    const canvas = document.createElement('canvas');

    canvas.width = canvas.height = Math.min(size, side);

    const context = canvas.getContext('2d');

    // JPEG has no transparency, so a transparent PNG would turn black without this
    context.fillStyle = '#FFFFFF';
    context.fillRect(0, 0, canvas.width, canvas.height);
    context.drawImage(bitmap, (bitmap.width - side) / 2, (bitmap.height - side) / 2, side, side, 0, 0, canvas.width, canvas.height);
    bitmap.close?.();

    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));

    if (!blob) {
        throw new Error('The image could not be processed.');
    }

    return new File([blob], 'profile.jpg', { type: 'image/jpeg' });
}
