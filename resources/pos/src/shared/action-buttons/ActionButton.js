import React from 'react';
import {FontAwesomeIcon} from '@fortawesome/react-fontawesome';
import {faEye, faKey, faPenToSquare, faTrash, faFileImport} from '@fortawesome/free-solid-svg-icons';
import {placeholderText, getFormattedMessage} from '../sharedMethod';

const ActionButton = (props) => {
    const {
        goToEditProduct,
        item,
        onClickDeleteModel = true,
        isDeleteMode = true,
        isEditMode = true,
        goToDetailScreen,
        isViewIcon = false,
        isPasswordShow = false,
        goToChangePassword,
        onClickPost = null,
        isPostMode = false,
        isPosting = false
    } = props;

    return (
        <>
            {isPasswordShow ? (
                <button
                    title={placeholderText("user.input.password.label")}
                    className="btn text-warning px-2 fs-3 ps-0 border-0"
                    onClick={(e) => {
                        e.stopPropagation();
                        goToChangePassword(item.id);
                    }}
                >
                    <FontAwesomeIcon icon={faKey} />
                </button>
            ) : null}
            {isViewIcon ?
                <button title={placeholderText('globally.view.tooltip.label')}
                        className='btn text-success px-2 fs-3 ps-0 border-0'
                        onClick={(e) => {
                            e.stopPropagation();
                            goToDetailScreen(item.id)
                        }}>
                    <FontAwesomeIcon icon={faEye}/>
                </button> : null
            }
            {isEditMode && (
                <button title={placeholderText('globally.edit.tooltip.label')}
                        className='btn text-primary fs-3 border-0 px-xxl-2 px-1'
                        onClick={(e) => {
                            e.stopPropagation();
                            goToEditProduct(item);
                        }}
                >
                    <FontAwesomeIcon icon={faPenToSquare}/>
                </button>)
            }
            {isDeleteMode === false ? null :
                <button title={placeholderText('globally.delete.tooltip.label')}
                        className='btn px-2 pe-0 text-danger fs-3 border-0'
                        onClick={(e) => {
                            e.stopPropagation();
                            onClickDeleteModel(item);
                        }}
                >
                    <FontAwesomeIcon icon={faTrash}/>
                </button>
            }
            {isPostMode && onClickPost && (
                <button 
                    title={getFormattedMessage("globally.post.label") || "Post"}
                    className='btn px-2 text-success fs-3 border-0'
                    onClick={(e) => {
                        e.stopPropagation();
                        onClickPost(item);
                    }}
                    disabled={isPosting}
                >
                    <FontAwesomeIcon icon={faFileImport}/>
                </button>
            )}
        </>
    )
};
export default ActionButton;
