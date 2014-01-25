//------------------------------------------------------------------------------
// Searches in for a string in a 2d array where array[i][0] will be what the 
// needle is compared against. All operations are case insensitive. The array 
// needs to be sorted based on its string.
//------------------------------------------------------------------------------
function mapBinarySearch(needle, haystack) {
  needle = needle.toLowerCase();

  var low = 0;
  var high = haystack.length - 1;

  while(low < high) {
    mid = Math.floor((high + low) / 2);

    if(needle < haystack[mid][0].toLowerCase()) {
      high = mid - 1;
    } else if(needle > haystack[mid][0].toLowerCase()) {
      low = mid;
    } else {
      return mid;
    }
  }

  return -1;
}

//------------------------------------------------------------------------------
// This sorts the specified 2d array based on its first element under the 
// assumption that it will be a string. It does this using a quickSort algorithm
// for performance.
//------------------------------------------------------------------------------
function mapQuickSort(unsorted, begin, end) {
  if(begin < end) {
    pivot = partition(unsorted, begin, end);

    mapQuickSort(unsorted, begin, pivot);
    mapQuickSort(unsorted, pivot + 1, end);
  }
}

//------------------------------------------------------------------------------
// This is used by the mapQuickSort method to split the array into parts.
//------------------------------------------------------------------------------
function partition(unsorted, begin, end) {
  var pivot = unsorted[begin][0].toLowerCase();
  var store = begin - 1;

  var stop = end + 1;

  while(store < stop) {
    store++;

    while(unsorted[store][0].toLowerCase() < pivot && store < stop) {
      store++;
    }
    stop--;
    while(unsorted[stop][0].toLowerCase() > pivot && store < stop) {
      stop--;
    }

    if(store < stop) {
      var temp = unsorted[store];
      unsorted[store] = unsorted[stop];
      unsorted[stop] = temp;
    }
  }

  return stop;
}

//------------------------------------------------------------------------------
// This sorts the specified 2d array based on its first element under the 
// assumption that it will be a string. It does this using a quickSort algorithm
// for performance.
//------------------------------------------------------------------------------
function mapQuickSortByValue(unsorted, begin, end) {
  if(begin < end) {
    pivot = partitionByValue(unsorted, begin, end);

    mapQuickSortByValue(unsorted, begin, pivot);
    mapQuickSortByValue(unsorted, pivot + 1, end);
  }
}

//------------------------------------------------------------------------------
// This is used by the mapQuickSort method to split the array into parts.
//------------------------------------------------------------------------------
function partitionByValue(unsorted, begin, end) {
  var pivot = unsorted[begin][1];
  var store = begin - 1;

  var stop = end + 1;

  while(store < stop) {
    store++;

    while(unsorted[store][1] < pivot && store < stop) {
      store++;
    }
    stop--;
    while(unsorted[stop][1] > pivot && store < stop) {
      stop--;
    }

    if(store < stop) {
      var temp = unsorted[store];
      unsorted[store] = unsorted[stop];
      unsorted[stop] = temp;
    }
  }

  return stop;
}

//------------------------------------------------------------------------------
// This finds where to put bit in a sorted 2d array shelf so that shelf remains 
// sorted.
//------------------------------------------------------------------------------
function quickInsert(shelf, bit) {
  var item = bit[0].toLowerCase();
  var iterator;
  var index = 0;

  if(shelf[0]) {
    if(item < shelf[0][0].toLowerCase()) {
      index = 0;
    } else if(item > shelf[shelf.length - 1][0]) {
      index = shelf.length;
    }

    for( iterator = 0; iterator < shelf.length - 1; iterator++) {
      if((item >= shelf[iterator][0].toLowerCase()) && (item <= shelf[iterator + 1][0].toLowerCase())) {
        index = iterator + 1;
      }
    }
  }

  shelf.splice(index, 0, bit);
}

//------------------------------------------------------------------------------
// This finds where to put bit in a sorted 2d array shelf so that shelf remains 
// sorted.
//------------------------------------------------------------------------------
function quickInsertByValue(shelf, bit) {
  var item = bit[1];
  var iterator;
  var index = 0;

  if(shelf[0]) {
    if(item < shelf[0][1]) {
      index = 0;
    } else if(item > shelf[shelf.length - 1][1]) {
      index = shelf.length;
    }

    for( iterator = 0; iterator < shelf.length - 1; iterator++) {
      if((item >= shelf[iterator][1]) && (item <= shelf[iterator + 1][1])) {
        index = iterator + 1;
      }
    }

    shelf.splice(index, 0, bit);
  }
}

//------------------------------------------------------------------------------
// searchByValue() looks for an item in a 2d array based on the second value 
// contained in the array's pairs. It returns the index of the item.
//------------------------------------------------------------------------------
function searchByValue(needle, haystack) {
  var index;
  var location = -1;

  for(index in haystack) {
    if(haystack[index][1] == needle) {
      location = index;
    }
  }

  return location;
}

//----------------------------------------------------------------------
// This function removes an item from an array if it is present, then 
// adjusts the array to fit.
//----------------------------------------------------------------------
function removeByValue(arr, val) {
  for(var i = 0; i < arr.length; i++) {
    if(arr[i] == val) {
      arr.splice(i, 1);
      break;
    }
  }
}

// These two draw functions are adapted from - http://www.html5laboratory.com/creating-a-bar-chart-with-canvas.php

//----------------------------------------------------------------------
// Draws a line on a canvas context using specified points.
//----------------------------------------------------------------------
function drawLine(contextO, startx, starty, endx, endy) {
  contextO.beginPath();
  contextO.moveTo(startx, starty);
  contextO.lineTo(endx, endy);
  contextO.closePath();
  contextO.stroke();
}

//----------------------------------------------------------------------
// Draws a rectangle on a canvas context using the dimensions specified.
//----------------------------------------------------------------------
function drawRectangle(contextO, x, y, w, h, fill) {
  contextO.beginPath();
  contextO.rect(x, y, w, h);
  contextO.closePath();
  contextO.stroke();
  if(fill) {
    contextO.fill();
  }
}
