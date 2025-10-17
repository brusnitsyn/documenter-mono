// composables/useFileDownload.js
export const useFileDownload = () => {
    const downloadFile = async (url, data, filename = 'file', method = 'post') => {
        try {
            const response = await axios({
                method,
                url,
                data: method === 'post' ? data : null,
                params: method === 'get' ? data : null,
                responseType: 'blob'
            });

            // Проверяем, что это действительно файл, а не ошибка
            const contentType = response.headers['content-type'];
            if (contentType.includes('application/json')) {
                // Это JSON ошибка, а не файл
                const errorData = JSON.parse(await response.data.text());
                throw new Error(errorData.error || 'Download failed');
            }

            // Создаем blob URL
            const blob = new Blob([response.data], {
                type: response.headers['content-type']
            });
            const downloadUrl = window.URL.createObjectURL(blob);

            // Создаем временную ссылку для скачивания
            const link = document.createElement('a');
            link.href = downloadUrl;

            link.download = filename;

            // Имитируем клик для скачивания
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            // Очищаем URL
            window.URL.revokeObjectURL(downloadUrl);

            return true;

        } catch (error) {
            console.error('Download error:', error);
            throw error;
        }
    };

    const getFileNameFromResponse = (response) => {
        const contentDisposition = response.headers['content-disposition'];
        let fileName = 'document.docx';

        if (contentDisposition) {
            const filenameMatch = contentDisposition.match(/filename="?(.+)"?/);
            if (filenameMatch && filenameMatch[1]) {
                fileName = filenameMatch[1].replace(/"/g, '');
            }
        }

        return fileName;
    };

    return {
        downloadFile
    };
};
