<?php

// This file is part of the FMI IMPEx tools.
//
// Copyright 2014- Finnish Meteorological Institute
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.

// #####################################################################################################
//
// 								          getDataPointValue.php
//
// #####################################################################################################

// =====================================================================================================
// Compute interpolated values for parameters defined in $params input variable.
//
//	$params = array
//		string 'ResourceID'		x	Resource ID of the <NumericalOutput> element in tree.xml
//		string 'Variable'			List of individual parameters from the selected
//									NumericalOutput separated by a comma.
//									Identified by Spase/NumericalOutput/Parameter/ParameterKey.
//									By default: all parameters in the NumericalOutput are sent back.
//		url 'url_XYZ'			x	URL of the VOTable file containing the 3D coordinates.
//									Suggested field name according to
//									Spase/SimulationRun/SimulationDomain/CoordinatesLabel
//		array 'extraParams'			Associative array of additional parameters. Different for each SMDB
//			string 'InterpolationMethod'	Enumerated list of interpolation method to be defined
//											(NearestGridPoint, Linear (default))
//			string 'OutputFiletype'			Enumerated list of output file formats.
//											FMI supports ASCII, VOTable (default) and netCDF.
//
//	Parameters indicated by 'x' are mandatory.
// =====================================================================================================

// =====================================================================================================
// Version 2.0 2015-06-01		First release of the new code.
// =====================================================================================================

function getDataPointValue($params) {

	// --------------------------------
	// Log caller info and method name
	// --------------------------------

	log_method("getDataPointValue", $params);


	// ----------------------------------------------------------------------------
	// Define a structure to handle Input parameters.
	// The user given values will be stored in the 'data' field.
	// The 'data' field is initially set to default value.
	// ----------------------------------------------------------------------------

	$InputParams = [
		'ResourceID'  => ['type' => 'string', 'mandatory' => true,  'data' => null],
		'Variable'    => ['type' => 'string', 'mandatory' => false, 'data' => ""],
		'url_XYZ'	  => ['type' => 'string', 'mandatory' => true,  'data' => null],
		'extraParams' => [
			'InterpolationMethod' => ['type' => 'string', 'mandatory' => false, 'enum' => ['Linear', 'NearestGridPoint'], 'data' => 'Linear'],
			'OutputFileType'      => ['type' => 'string', 'mandatory' => false, 'enum' => ['netCDF', 'VOTable', 'ASCII'], 'data' => 'VOTable']
		]
	];


	// --------------------------------------------------
	// Handle the user given input parameters ($params).
	// --------------------------------------------------

	// Check that mandatory parameters are included in user given input parameter
	// values ($params) and copy the values of all parameters to $InputParams.
	// Check also that given parameters are of proper type.
	// Set also default values to parameters that the user has not defined.
	$InputParams = check_input_params($InputParams, $params);

	// Check the ResourceID and convert to new format if it is in old format (FMI only).
	$InputParams['ResourceID']['data'] = check_resourceID($InputParams['ResourceID']['data']);

	// Load the tree file and get the NumericalOutput and SimulationRun elements
	$tree_xml = get_Tree($InputParams['ResourceID']['data']);
	$num_output_elem = get_NumericalOutput($tree_xml, $InputParams['ResourceID']['data']);
	$simulation_run_elem = get_SimulationRun($tree_xml, $num_output_elem);

	// Read the user defined list of variables into $InputParams['Variable']['data']
	// as an array (key = variable name, value = variable description) and check
	// that $num_output_elem contains the given variable names.
	$InputParams['Variable']['data'] = get_variable_list($InputParams['Variable']['data'], $num_output_elem);

	// Copy the point file defined in parameter 'url_XYZ' into local disk
	$point_file_name = get_URL($InputParams['url_XYZ']['data']);

	// Read some metadata from $tree_xml into $metadata global variable
	get_metadata($tree_xml, $simulation_run_elem, $num_output_elem, $InputParams);


	// --------------------------------------------------
	// Handle the computation of interpolated values.
	// --------------------------------------------------

	$final_file_name = handle_interpolation($point_file_name, $num_output_elem, $simulation_run_elem, $InputParams);


	// --------------
	// Final touches
	// --------------

	clean_up([$point_file_name]);
	return(returnURL($final_file_name));

}	// THE END
?>
