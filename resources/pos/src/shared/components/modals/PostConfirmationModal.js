import React, { useCallback, useEffect } from 'react';
import SweetAlert from 'react-bootstrap-sweetalert';
import warning from "../../../assets/images/warning.png"
import { getFormattedMessage, placeholderText } from "../../sharedMethod";

const PostConfirmationModal = (props) => {
    const { onCancel, onConfirm, title = "Are you sure you want to post this item?" } = props;

    const escFunction = useCallback((event) => {
        if (event.keyCode === 27) {
            onCancel(false);
        }
    }, [onCancel]);

    useEffect(() => {
        document.addEventListener('keydown', escFunction, false);
        return () => {
            document.removeEventListener('keydown', escFunction, false);
        };
    }, [escFunction]);

    return (
        <SweetAlert
            custom
            confirmBtnBsStyle='success mb-3 fs-5 rounded'
            cancelBtnBsStyle='secondary mb-3 fs-5 rounded text-white'
            confirmBtnText={getFormattedMessage("globally.post.button") || "Post"}
            cancelBtnText={getFormattedMessage('delete-modal.no-btn') || "Cancel"}
            title={title}
            onConfirm={onConfirm}
            onCancel={onCancel}
            showCancel
            focusConfirmBtn={false}
            focusCancelBtn={true}
            customIcon={warning}
        >
            <div className='sweet-text'>
                <p>{getFormattedMessage("globally.post.confirmation") || "This action cannot be undone."}</p>
            </div>
        </SweetAlert>
    )
};
export default PostConfirmationModal;
