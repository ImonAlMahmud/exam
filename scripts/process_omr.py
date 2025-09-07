# scripts/process_omr.py
import cv2
import numpy as np
import sys
import json
import os
from imutils import contours # pip install imutils

# --- Configuration (Adjust these based on your OMR sheet design) ---
NUM_QUESTIONS_PER_COLUMN = 25  # Number of questions in one column per grid
NUM_COLUMNS_PER_PAGE = 4       # Total number of question columns on the OMR sheet
NUM_OPTIONS_PER_QUESTION = 4   # Number of bubbles/options per question (A, B, C, D)

# Image processing thresholds and filters
# --- TUNED FOR YOUR IMAGE ---
MIN_MARKER_AREA = 300         # Reduced minimum area for markers
MAX_MARKER_AREA = 2000        # Adjusted maximum area
MARKER_THRESHOLD_VALUE = 120  # Increased threshold value for dark markers (was 80)
# This will make lighter dark areas also count as markers. Adjust as needed.

MARKER_CANNY_LOW = 75
MARKER_CANNY_HIGH = 200

# Bubble detection (these might need separate tuning after alignment is fixed)
MIN_BUBBLE_AREA = 100       
MAX_BUBBLE_AREA = 500       
FILL_THRESHOLD_PERCENT = 0.5 # 50% fill to consider a bubble as "marked"

# --- Helper Functions ---
def order_points(pts):
    """
    Orders a list of 4 points in top-left, top-right, bottom-right, and bottom-left order.
    """
    rect = np.zeros((4, 2), dtype="float32")
    s = pts.sum(axis=1)
    rect[0] = pts[np.argmin(s)] # Top-left
    rect[2] = pts[np.argmax(s)] # Bottom-right

    diff = np.diff(pts, axis=1)
    rect[1] = pts[np.argmin(diff)] # Top-right
    rect[3] = pts[np.argmax(diff)] # Bottom-left
    return rect

def four_point_transform(image, pts):
    """
    Performs a four-point perspective transform.
    `pts`: The 4 reference points (top-left, top-right, bottom-right, bottom-left)
    """
    rect = order_points(pts)
    (tl, tr, br, bl) = rect

    # Compute width of new image
    widthA = np.sqrt(((br[0] - bl[0]) ** 2) + ((br[1] - bl[1]) ** 2))
    widthB = np.sqrt(((tr[0] - tl[0]) ** 2) + ((tr[1] - tl[1]) ** 2))
    maxWidth = max(int(widthA), int(widthB))

    # Compute height of new image
    heightA = np.sqrt(((tr[0] - br[0]) ** 2) + ((tr[1] - br[1]) ** 2))
    heightB = np.sqrt(((tl[0] - bl[0]) ** 2) + ((tl[1] - bl[1]) ** 2))
    maxHeight = max(int(heightA), int(heightB))

    dst = np.array([
        [0, 0],
        [maxWidth - 1, 0],
        [maxWidth - 1, maxHeight - 1],
        [0, maxHeight - 1]], dtype="float32")

    M = cv2.getPerspectiveTransform(rect, dst)
    warped = cv2.warpPerspective(image, M, (maxWidth, maxHeight))
    
    return warped

def find_alignment_markers_and_transform(image, debug_image_output=False):
    """
    Detects the four black square alignment markers and applies perspective correction.
    """
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    blurred = cv2.GaussianBlur(gray, (5, 5), 0)
    # Use a higher threshold value if markers are light, lower if they are very dark
    # THRESH_BINARY_INV means dark regions become white (255), light regions become black (0)
    thresh = cv2.threshold(blurred, MARKER_THRESHOLD_VALUE, 255, cv2.THRESH_BINARY_INV)[1]

    # --- Debugging: Save thresholded marker image ---
    if debug_image_output:
        debug_path = os.path.join(os.path.dirname(__file__), '..', 'uploads', 'omr_debug')
        if not os.path.exists(debug_path): os.makedirs(debug_path)
        cv2.imwrite(os.path.join(debug_path, "debug_omr_marker_thresh.png"), thresh) # Save thresholded marker image


    # Find contours
    cnts, _ = cv2.findContours(thresh.copy(), cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    
    marker_contours = []
    for c in cnts:
        peri = cv2.arcLength(c, True)
        approx = cv2.approxPolyDP(c, 0.04 * peri, True) # Approximate contour with fewer vertices
        
        # Filter for quadrilateral shapes (the markers are squares)
        if len(approx) == 4:
            (x, y, w, h) = cv2.boundingRect(approx)
            ar = w / float(h)
            area = cv2.contourArea(c)
            # Filter by aspect ratio (close to 1 for squares) and reasonable size
            if 0.8 <= ar <= 1.2 and MIN_MARKER_AREA <= area <= MAX_MARKER_AREA:
                marker_contours.append(approx)
    
    # Sort markers to ensure consistent order (e.g., top-left, top-right, bottom-right, bottom-left)
    # This might require custom sorting based on coordinates if simple contour sort doesn't work.
    # For now, rely on `order_points` function in `four_point_transform`.

    if len(marker_contours) != 4:
        raise Exception(f"Expected 4 alignment markers, but found {len(marker_contours)}. Check OMR sheet design/scan quality or adjust MIN/MAX_MARKER_AREA/THRESHOLD. (Min Area: {MIN_MARKER_AREA}, Max Area: {MAX_MARKER_AREA}, Threshold: {MARKER_THRESHOLD_VALUE})")

    # Get the centroid of each marker for perspective transform
    marker_points = []
    for c in marker_contours:
        M = cv2.moments(c)
        if M["m00"] != 0:
            cX = int(M["m10"] / M["m00"])
            cY = int(M["m01"] / M["m00"])
            marker_points.append([cX, cY])
        else:
            raise Exception("Could not compute centroid for a marker contour.")
    
    marker_points = np.array(marker_points, dtype="float32")
    
    # Perform perspective transform
    warped = four_point_transform(image, marker_points)
    
    if debug_image_output:
        debug_path = os.path.join(os.path.dirname(__file__), '..', 'uploads', 'omr_debug')
        if not os.path.exists(debug_path): os.makedirs(debug_path)
        
        temp_img_markers = image.copy()
        for c in marker_contours:
            # Draw green contours for detected markers on a copy of original image
            cv2.drawContours(temp_img_markers, [c], -1, (0, 255, 0), 2)
        
        cv2.imwrite(os.path.join(debug_path, "debug_omr_markers.png"), temp_img_markers)
        cv2.imwrite(os.path.join(debug_path, "debug_omr_aligned.png"), warped)

    return warped

def find_bubbles_and_mark(processed_image, answer_key_data, debug_image_output=False):
    """
    Finds OMR bubbles, detects marked answers, and calculates the score.
    """
    gray = cv2.cvtColor(processed_image, cv2.COLOR_BGR2GRAY)
    thresh = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY_INV | cv2.THRESH_OTSU)[1] # Adaptive thresholding
    
    # --- Debugging: Save thresholded image ---
    if debug_image_output:
        debug_path = os.path.join(os.path.dirname(__file__), '..', 'uploads', 'omr_debug')
        if not os.path.exists(debug_path): os.makedirs(debug_path)
        cv2.imwrite(os.path.join(debug_path, "debug_omr_thresh_bubbles.png"), thresh) # Save thresholded image


    # Find contours for potential bubbles
    cnts, _ = cv2.findContours(thresh.copy(), cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    
    bubble_contours = []
    for c in cnts:
        (x, y, w, h) = cv2.boundingRect(c)
        ar = w / float(h)
        # Filter contours by size and aspect ratio to isolate circular/oval bubbles
        if 0.8 <= ar <= 1.2 and MIN_BUBBLE_AREA <= cv2.contourArea(c) <= MAX_BUBBLE_AREA:
            bubble_contours.append(c)

    if len(bubble_contours) < len(answer_key_data) * NUM_OPTIONS_PER_QUESTION:
        raise Exception(f"Found {len(bubble_contours)} potential bubbles, expected at least {len(answer_key_data) * NUM_OPTIONS_PER_QUESTION}. Check bubble detection filters or OMR sheet design.")

    # Sort all bubbles primarily by their Y-coordinate (row)
    bubble_contours_sorted_y = contours.sort_contours(bubble_contours, method="top-to-bottom")[0]

    student_marked_options = {} # Store student's answers: {question_number: marked_option_index}
    
    total_questions = len(answer_key_data)
    
    if len(bubble_contours_sorted_y) % NUM_OPTIONS_PER_QUESTION != 0:
        raise Exception(f"Total bubbles found ({len(bubble_contours_sorted_y)}) is not a multiple of options per question ({NUM_OPTIONS_PER_QUESTION}). Irregular bubble count.")

    # Group bubbles for each question
    # This part needs to accurately map detected bubbles to their logical question number and option.
    # Given the 4-column layout, we will iterate through question rows.
    
    # Assuming the aligned image has a consistent grid.
    # We need to refine how bubbles are grouped by actual question number
    # For a 100-question, 4-column layout, the Y-sorting is crucial, then X-sorting within question groups.

    # Group bubbles by approximate row.
    # This requires knowing the vertical pitch between question rows.
    # For 100 questions, 25 per column means 25 rows per section.
    
    # A more robust way: find centroids of all bubbles, then cluster them by Y for rows, then by X for columns/options.
    
    # For now, let's assume the top-to-bottom sorting is sufficient for sequential processing,
    # and the answer key maps directly to this sequence.
    
    for q_idx in range(total_questions): # Iterate through each question number from 1 to 100
        # Calculate the starting index of bubbles for the current question
        start_bubble_index = q_idx * NUM_OPTIONS_PER_QUESTION
        end_bubble_index = start_bubble_index + NUM_OPTIONS_PER_QUESTION
        
        current_question_bubbles_raw = bubble_contours_sorted_y[start_bubble_index : end_bubble_index]
        
        if not current_question_bubbles_raw or len(current_question_bubbles_raw) != NUM_OPTIONS_PER_QUESTION:
            raise Exception(f"Not enough bubbles found for question {q_idx + 1}. Expected {NUM_OPTIONS_PER_QUESTION}, found {len(current_question_bubbles_raw)}.")
            
        # Sort these 4 bubbles left-to-right to identify option 1, 2, 3, 4
        current_question_bubbles_sorted_x = contours.sort_contours(current_question_bubbles_raw, method="left-to-right")[0]
        
        bubbled_option_index = 0 # 1-based index of the marked option
        max_fill_pixels = 0
        
        for (option_idx, c_bubble) in enumerate(current_question_bubbles_sorted_x):
            mask = np.zeros(thresh.shape, dtype="uint8")
            cv2.drawContours(mask, [c_bubble], -1, 255, -1)
            mask = cv2.bitwise_and(thresh, thresh, mask=mask)
            
            filled_pixels = cv2.countNonZero(mask)
            
            (x, y, w, h) = cv2.boundingRect(c_bubble)
            bubble_area = w * h
            
            if bubble_area > 0:
                fill_percentage = filled_pixels / float(bubble_area)
            else:
                fill_percentage = 0
            
            if fill_percentage >= FILL_THRESHOLD_PERCENT:
                if filled_pixels > max_fill_pixels:
                    max_fill_pixels = filled_pixels
                    bubbled_option_index = option_idx + 1 # 1-based index (1 for A, 2 for B, etc.)
        
        question_number_str = str(q_idx + 1) # Question number based on loop index
        student_marked_options[question_number_str] = bubbled_option_index # 0 if no option marked

        # --- Debugging: Draw on image for visual feedback ---
        if debug_image_output:
            if bubbled_option_index > 0: # If student marked something
                (x, y, w, h) = cv2.boundingRect(current_question_bubbles_sorted_x[bubbled_option_index - 1])
            else: # If nothing marked for the question, use first bubble for text placement
                (x, y, w, h) = cv2.boundingRect(current_question_bubbles_sorted_x[0])
            
            debug_color = (255, 0, 0) # Default Blue for detection
            
            if bubbled_option_index != 0: # If student marked something
                if question_number_str in answer_key_data and bubbled_option_index == answer_key_data[question_number_str]:
                    debug_color = (0, 255, 0) # Green for correct
                else:
                    debug_color = (0, 0, 255) # Red for incorrect
            else: # No answer marked by student
                debug_color = (128, 128, 128) # Gray for unanswered

            cv2.putText(processed_image, f"Q{question_number_str}:{bubbled_option_index}", (x, y - 5), cv2.FONT_HERSHEY_SIMPLEX, 0.4, debug_color, 1)
            if bubbled_option_index > 0:
                cv2.rectangle(processed_image, (x,y), (x+w, y+h), debug_color, 1) # Draw a box around the marked bubble

    # Calculate score based on student_marked_options and answer_key_data
    score = 0
    for q_num_str, correct_option_index in answer_key_data.items():
        if q_num_str in student_marked_options and student_marked_options[q_num_str] == correct_option_index:
            score += 1
            
    # total_questions is already derived from len(answer_key_data) earlier.
    total_questions = len(answer_key_data)

    if debug_image_output:
        debug_path = os.path.join(os.path.dirname(__file__), '..', 'uploads', 'omr_debug')
        if not os.path.exists(debug_path): os.makedirs(debug_path)
        
        cv2.imwrite(os.path.join(debug_path, "debug_omr_processed.png"), processed_image)

    return score, total_questions

def process_omr_sheet_main(image_path, answer_key_path):
    try:
        with open(answer_key_path, 'r') as f:
            answer_key = json.load(f)

        original_image = cv2.imread(image_path)
        if original_image is None:
            raise Exception(f"Could not load image: {image_path}. Check file path and integrity.")
        
        # Align the image
        aligned_image = find_alignment_markers_and_transform(original_image, debug_image_output=True) # Debug aligned image
        
        # Find bubbles and calculate score
        score, total = find_bubbles_and_mark(aligned_image, answer_key, debug_image_output=True) # Set True for debugging

        result = {
            "status": "success",
            "score": score,
            "total": total,
            "message": "OMR sheet processed successfully."
        }
        return json.dumps(result)

    except Exception as e:
        error_result = {
            "status": "error",
            "message": f"OMR processing failed: {str(e)}",
            "image_path": os.path.basename(image_path),
            "answer_key_path": os.path.basename(answer_key_path)
        }
        return json.dumps(error_result)

if __name__ == "__main__":
    if len(sys.argv) != 3:
        error_output = json.dumps({"status": "error", "message": "Invalid number of arguments. Expected image_path and answer_key_path."})
        print(error_output)
        sys.exit(1)

    image_file_path = sys.argv[1]
    key_file_path = sys.argv[2]
    
    if not os.path.exists(image_file_path):
        error_output = json.dumps({"status": "error", "message": f"Image file not found: {image_file_path}"})
        print(error_output)
        sys.exit(1)
    if not os.path.exists(key_file_path):
        error_output = json.dumps({"status": "error", "message": f"Answer key file not found: {key_file_path}"})
        print(error_output)
        sys.exit(1)

    output = process_omr_sheet_main(image_file_path, key_file_path)
    print(output)
    sys.exit(0)