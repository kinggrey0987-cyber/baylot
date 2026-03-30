
    document.addEventListener('DOMContentLoaded', () => {
        window.BayLotMap.initPicker('pickerMap', 'lat', 'lng', null, 'location_name');

        const maxImages = 10;
        const imageInputsContainer = document.getElementById('imageInputsContainer');

        if (imageInputsContainer) {
            const createImageRow = () => {
                const row = document.createElement('div');
                row.className = 'input-group listing-image-row';

                const input = document.createElement('input');
                input.type = 'file';
                input.name = 'images[]';
                input.accept = 'image/png,image/jpeg,image/webp';
                input.className = 'form-control listing-image-input';

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'btn btn-outline-danger listing-image-remove';
                removeButton.textContent = 'Remove';

                removeButton.addEventListener('click', () => {
                    row.remove();
                    normalizeImageRows();
                });

                input.addEventListener('change', () => {
                    normalizeImageRows();
                });

                row.append(input, removeButton);
                return row;
            };

            const normalizeImageRows = () => {
                const rows = Array.from(imageInputsContainer.querySelectorAll('.listing-image-row'));
                const filledRows = rows.filter((row) => {
                    const input = row.querySelector('.listing-image-input');
                    return input && input.files && input.files.length > 0;
                });

                const emptyRows = rows.filter((row) => {
                    const input = row.querySelector('.listing-image-input');
                    return !input || !input.files || input.files.length === 0;
                });

                if (rows.length === 0) {
                    imageInputsContainer.appendChild(createImageRow());
                    return normalizeImageRows();
                }

                if (emptyRows.length === 0 && rows.length < maxImages) {
                    imageInputsContainer.appendChild(createImageRow());
                } else if (emptyRows.length > 1) {
                    emptyRows.slice(0, -1).forEach((row) => row.remove());
                }

                const updatedRows = Array.from(imageInputsContainer.querySelectorAll('.listing-image-row'));
                updatedRows.forEach((row) => {
                    const input = row.querySelector('.listing-image-input');
                    const removeButton = row.querySelector('.listing-image-remove');
                    const hasFile = input && input.files && input.files.length > 0;

                    if (!removeButton) {
                        return;
                    }

                    removeButton.disabled = !hasFile && updatedRows.length === 1;
                });
            };

            imageInputsContainer.querySelectorAll('.listing-image-row').forEach((row) => {
                const input = row.querySelector('.listing-image-input');
                const removeButton = row.querySelector('.listing-image-remove');

                input?.addEventListener('change', normalizeImageRows);
                removeButton?.addEventListener('click', () => {
                    row.remove();
                    normalizeImageRows();
                });
            });

            normalizeImageRows();
        }
    });

